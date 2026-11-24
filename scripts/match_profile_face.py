#!/usr/bin/env python3
"""Compare two server-held images with OpenCV Zoo YuNet and SFace."""

import json
import math
import os
import sys

try:
    import cv2  # type: ignore  # Installed in the container runtime; script fails closed when absent.
except ImportError:
    print(json.dumps({"match": False, "code": "MATCHER_UNAVAILABLE"}))
    sys.exit(2)


def result(match, code, similarity=0.0, frame_count=1, frame_consistent=True, liveness_passed=False):
    print(json.dumps({
        "match": match,
        "code": code,
        "similarity": round(float(similarity), 4),
        "frame_count": frame_count,
        "frame_consistent": frame_consistent,
        "liveness_passed": liveness_passed,
    }))


def validate_profile_image(image_path, detector_path, recognizer_path):
    """Check enrollment-photo quality with the same detector used during matching."""
    if not all(os.path.isfile(path) for path in (image_path, detector_path, recognizer_path)):
        print(json.dumps({"valid": False, "code": "MATCHER_UNAVAILABLE"}))
        return 2

    try:
        detector = cv2.FaceDetectorYN.create(
            detector_path, "", (320, 320), score_threshold=0.9,
            nms_threshold=0.3, top_k=10
        )
        recognizer = cv2.FaceRecognizerSF.create(recognizer_path, "")
        face_feature(image_path, detector, recognizer, "PROFILE")
        print(json.dumps({"valid": True, "code": "PROFILE_READY"}))
        return 0
    except ValueError as exc:
        print(json.dumps({"valid": False, "code": str(exc)}))
        return 0
    except Exception:
        print(json.dumps({"valid": False, "code": "MATCHER_UNAVAILABLE"}))
        return 2


def pose_offset(face):
    """A 2D turn proxy from YuNet eye/nose landmarks, not a 3D/PAD estimate.

    Project onto the eye line to avoid treating in-plane head tilt as a turn.
    YuNet landmark indices are documented in FaceDetectorYN::detect().
    """
    dx, dy = float(face[6] - face[4]), float(face[7] - face[5])
    length_squared = dx * dx + dy * dy
    if length_squared < 16 * 16:
        raise ValueError("PROFILE_FACE_TOO_SMALL")
    nx = float(face[8]) - (float(face[4]) + float(face[6])) / 2
    ny = float(face[9]) - (float(face[5]) + float(face[7])) / 2
    offset = (nx * dx + ny * dy) / length_squared
    if not math.isfinite(offset):
        raise ValueError("PROFILE_IMAGE_QUALITY")
    return offset


def validate_pose_offsets(offsets):
    if len(offsets) != 3 or not all(math.isfinite(value) for value in offsets):
        raise ValueError("PROFILE_POSE_VARIATION")
    if abs(offsets[0]) > 0.18:
        raise ValueError("PROFILE_POSE_FRONT")
    turns = [value - offsets[0] for value in offsets[1:]]
    # Opposite turns also work when a device's camera orientation is mirrored.
    if turns[0] * turns[1] >= 0 or min(map(abs, turns)) < 0.12 or max(map(abs, offsets)) > 0.8:
        raise ValueError("PROFILE_POSE_VARIATION")


def validate_views(paths, detector_path, recognizer_path, threshold_text):
    try:
        threshold = float(threshold_text)
        if not math.isfinite(threshold) or not 0 < threshold <= 1:
            raise RuntimeError("Invalid threshold")
        if not all(os.path.isfile(path) for path in (*paths, detector_path, recognizer_path)):
            raise RuntimeError("Models unavailable")
        detector = cv2.FaceDetectorYN.create(detector_path, "", (320, 320), score_threshold=0.9, nms_threshold=0.3, top_k=10)
        recognizer = cv2.FaceRecognizerSF.create(recognizer_path, "")
        captures = [face_feature(path, detector, recognizer, "PROFILE", include_pose=True) for path in paths]
        validate_pose_offsets([capture[2] for capture in captures])
        for index, first in enumerate(captures):
            for second in captures[index + 1:]:
                score = float(recognizer.match(first[0], second[0], cv2.FaceRecognizerSF_FR_COSINE))
                if not math.isfinite(score) or not -1 <= score <= 1:
                    raise RuntimeError("Invalid match result")
                if score < threshold:
                    raise ValueError("PROFILE_IDENTITY_MISMATCH")
        print(json.dumps({"valid": True, "code": "PROFILE_READY"}))
        return 0
    except ValueError as exc:
        print(json.dumps({"valid": False, "code": str(exc)}))
        return 0
    except Exception:
        print(json.dumps({"valid": False, "code": "MATCHER_UNAVAILABLE"}))
        return 2


def best_reference_score(references, feature, recognizer):
    scores = [float(recognizer.match(reference, feature, cv2.FaceRecognizerSF_FR_COSINE)) for reference in references]
    if not scores or not all(math.isfinite(score) and -1 <= score <= 1 for score in scores):
        raise RuntimeError("Invalid reference score")
    return max(scores)


def normalize_face_lighting(aligned):
    """Normalize local luminance after landmark alignment for steadier embeddings.

    Gentle exposure centering plus restrained CLAHE on LAB lightness reduces
    sensitivity to shadows and backlit classrooms without relaxing the cosine
    match threshold. Chroma is left untouched to avoid shifting skin colors.
    """
    lab = cv2.cvtColor(aligned, cv2.COLOR_BGR2LAB)
    lightness, a_channel, b_channel = cv2.split(lab)
    mean_l = float(lightness.mean())
    beta = max(-28.0, min(28.0, (128.0 - mean_l) * 0.35))
    balanced_l = cv2.convertScaleAbs(lightness, alpha=1.0, beta=beta) if abs(beta) >= 4.0 else lightness
    clahe = cv2.createCLAHE(clipLimit=1.9, tileGridSize=(4, 4))
    normalized_lightness = clahe.apply(balanced_l)
    normalized = cv2.merge((normalized_lightness, a_channel, b_channel))
    return cv2.cvtColor(normalized, cv2.COLOR_LAB2BGR)


def detect_primary_face(image, detector, kind):
    """Detect a single face using YuNet's optimal resolution window, then map boxes back."""
    height, width = image.shape[:2]
    max_side = max(width, height)
    if max_side > 720:
        det_scale = 640.0 / max_side
    elif max_side < 220:
        det_scale = min(1.8, 240.0 / max_side)
    else:
        det_scale = 1.0

    if abs(det_scale - 1.0) > 1e-6:
        det_w = max(100, int(round(width * det_scale)))
        det_h = max(100, int(round(height * det_scale)))
        interp = cv2.INTER_AREA if det_scale < 1.0 else cv2.INTER_LINEAR
        det_image = cv2.resize(image, (det_w, det_h), interpolation=interp)
    else:
        det_w, det_h = width, height
        det_image = image

    detector.setInputSize((det_w, det_h))
    _, faces = detector.detect(det_image)
    if faces is None or len(faces) == 0:
        raise ValueError(f"{kind}_FACE_NOT_FOUND")
    if len(faces) != 1:
        raise ValueError(f"{kind}_MULTIPLE_FACES")

    face = faces[0]
    if abs(det_scale - 1.0) > 1e-6:
        if hasattr(face, "copy"):
            face = face.copy()
            face[:14] = face[:14] / det_scale
        else:
            face = [float(v) / det_scale if i < 14 else v for i, v in enumerate(face)]

    if len(face) >= 15 and float(face[14]) < 0.9:
        raise ValueError(f"{kind}_FACE_NOT_FOUND")
    return face, width, height


def scale_invariant_blur_variance(gray_crop):
    """Measure focus variance at a bounded canonical face scale so high-res portraits pass cleanly."""
    ch, cw = gray_crop.shape[:2]
    max_dim = max(cw, ch)
    if max_dim > 220:
        scale = 160.0 / max_dim
        sample = cv2.resize(
            gray_crop,
            (max(40, int(round(cw * scale))), max(40, int(round(ch * scale)))),
            interpolation=cv2.INTER_AREA,
        )
    else:
        sample = gray_crop
    return float(cv2.Laplacian(sample, cv2.CV_64F).var())


def face_feature(image_path, detector, recognizer, kind, include_pose=False):
    image = cv2.imread(image_path, cv2.IMREAD_COLOR)
    if image is None:
        raise ValueError(f"{kind}_IMAGE_UNREADABLE")
    height, width = image.shape[:2]
    if min(width, height) < 100 or max(width, height) > 2000:
        raise ValueError(f"{kind}_IMAGE_QUALITY")

    face, width, height = detect_primary_face(image, detector, kind)
    x, y, w, h = map(float, face[:4])
    if w < 60 or h < 60:
        raise ValueError(f"{kind}_FACE_TOO_SMALL")

    x0, y0 = max(0, int(x)), max(0, int(y))
    x1, y1 = min(width, math.ceil(x + w)), min(height, math.ceil(y + h))
    if x1 - x0 >= 32 and y1 - y0 >= 32:
        native_gray = cv2.cvtColor(image[y0:y1, x0:x1], cv2.COLOR_BGR2GRAY)
        mean, contrast = float(native_gray.mean()), float(native_gray.std())
        if mean < 32 or mean > 235 or contrast < 11:
            raise ValueError("LIVE_IMAGE_LIGHTING" if kind == "LIVE" else f"{kind}_IMAGE_QUALITY")
        if scale_invariant_blur_variance(native_gray) < 20.0:
            raise ValueError(f"{kind}_IMAGE_BLURRY")

    aligned = recognizer.alignCrop(image, face)
    aligned_gray = cv2.cvtColor(aligned, cv2.COLOR_BGR2GRAY)
    if float(cv2.Laplacian(aligned_gray, cv2.CV_64F).var()) < 18.0:
        raise ValueError(f"{kind}_IMAGE_BLURRY")

    # Apply identical luminance normalization to enrollment and live crops. This
    # helps SFace compare faces captured under different indoor lighting while
    # keeping the configured similarity threshold and multi-frame rule intact.
    feature = recognizer.feature(normalize_face_lighting(aligned))
    box = (
        float(face[0] + face[2] / 2) / width,
        float(face[1] + face[3] / 2) / height,
        float(face[2]) / width,
        float(face[3]) / height,
    )

    return (feature, box, pose_offset(face)) if include_pose else (feature, box)


def attendance_sample(path, detector, recognizer, pad):
    image = cv2.imread(path, cv2.IMREAD_COLOR)
    if image is None:
        raise ValueError("LIVE_IMAGE_UNREADABLE")
    height, width = image.shape[:2]
    if min(width, height) < 100 or max(width, height) > 2000:
        raise ValueError("LIVE_IMAGE_QUALITY")
    face, width, height = detect_primary_face(image, detector, "LIVE")
    x, y, w, h = map(float, face[:4])
    if min(w, h) < 65 or w < width * 0.23 or h < height * 0.27:
        raise ValueError("LIVE_FACE_TOO_SMALL")
    if x < 0 or y < 0 or x + w > width or y + h > height:
        raise ValueError("LIVE_FACE_POSITION")
    box = ((x + w / 2) / width, (y + h / 2) / height, w / width, h / height)
    if abs(box[0] - 0.5) > 0.20 or abs(box[1] - 0.5) > 0.23:
        raise ValueError("LIVE_FACE_POSITION")

    crop = image[int(y):math.ceil(y + h), int(x):math.ceil(x + w)]
    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
    mean, contrast = float(gray.mean()), float(gray.std())
    if mean < 45 or mean > 225 or contrast < 13:
        raise ValueError("LIVE_IMAGE_LIGHTING")
    if scale_invariant_blur_variance(gray) < 20.0:
        raise ValueError("LIVE_IMAGE_BLURRY")

    # Raw RGB face crop, normalized exactly as specified by the ONNX model.
    rgb = cv2.cvtColor(cv2.resize(crop, (128, 128)), cv2.COLOR_BGR2RGB).astype("float32")
    rgb = (rgb - (151.2405, 119.5950, 107.8395)) / (63.0105, 56.4570, 55.0035)
    pad.setInput(rgb.transpose(2, 0, 1)[None].astype("float32"))
    scores = pad.forward().reshape(-1)
    if len(scores) != 2 or not all(math.isfinite(float(score)) and 0 <= score <= 1 for score in scores):
        raise RuntimeError("Invalid PAD output")
    if abs(float(scores.sum()) - 1) > 0.02:
        raise RuntimeError("Invalid PAD probabilities")

    # Order eyes in the unmirrored image. Use the perpendicular eye axis
    # and mouth height to measure a change in pitch relative to the front view.
    left, right = sorted([(float(face[4]), float(face[5])), (float(face[6]), float(face[7]))])
    dx, dy = right[0] - left[0], right[1] - left[1]
    eye_length = math.hypot(dx, dy)
    if eye_length < 16 or abs(dy / eye_length) > 0.33:
        raise ValueError("LIVE_POSE_MISMATCH")
    ex, ey = (left[0] + right[0]) / 2, (left[1] + right[1]) / 2
    nx, ny = float(face[8]) - ex, float(face[9]) - ey
    mx, my = (float(face[10]) + float(face[12])) / 2 - ex, (float(face[11]) + float(face[13])) / 2 - ey
    mouth_height = (my * dx - mx * dy) / eye_length
    if mouth_height < 12:
        raise ValueError("LIVE_POSE_MISMATCH")
    pose = ((nx * dx + ny * dy) / eye_length ** 2, (ny * dx - nx * dy) / eye_length / mouth_height)
    aligned = recognizer.alignCrop(image, face)
    feature = recognizer.feature(normalize_face_lighting(aligned))
    return pose, box, float(scores[0]), feature, cv2.resize(gray, (32, 32)).astype("float32")


def attendance_motion(samples, poses):
    """Check ordered movements and stable holds using server-detected landmarks."""
    front_yaw, front_pitch = samples[0][0]
    if not abs(front_yaw) <= 0.14 or not 0.32 <= front_pitch <= 0.76:
        return False
    expected = ["front", "front"]
    for pose in poses[1:]:
        expected.extend(["front", pose, pose])

    for index, ((yaw, pitch), box, _, _, pixels) in enumerate(samples):
        turn, tilt = yaw - front_yaw, pitch - front_pitch
        limits = {
            "front": abs(turn) <= 0.10 and abs(tilt) <= 0.075,
            "left": 0.13 <= turn <= 0.65 and abs(tilt) <= 0.16,
            "right": -0.65 <= turn <= -0.13 and abs(tilt) <= 0.16,
            "up": -0.36 <= tilt <= -0.095 and abs(turn) <= 0.19,
            "down": 0.095 <= tilt <= 0.36 and abs(turn) <= 0.19,
        }
        if not limits[expected[index]]:
            return False
        if index == 0:
            continue
        prev_pose, prev_box, _, _, prev_pixels = samples[index - 1]
        if math.hypot(box[0] - prev_box[0], box[1] - prev_box[1]) > 0.20:
            return False
        if max(abs(box[i] - prev_box[i]) for i in (2, 3)) > 0.21:
            return False
        # Duplicate images sometimes differ only in metadata or compression.
        if float(abs(pixels - prev_pixels).mean()) < 0.30:
            return False
        if expected[index] == expected[index - 1] and (
            abs(yaw - prev_pose[0]) > 0.09 or abs(pitch - prev_pose[1]) > 0.075
        ):
            return False
    return True


def verify_attendance(samples, poses, references, recognizer, threshold, pad_threshold):
    count = len(samples)
    if not attendance_motion(samples, poses):
        result(False, "LIVE_POSE_MISMATCH", frame_count=count, frame_consistent=False)
        return
    weakest_pad = min(sample[2] for sample in samples)
    if weakest_pad < pad_threshold:
        code = "LIVENESS_FAILED" if weakest_pad < 0.5 else "LIVENESS_UNCERTAIN"
        result(False, code, frame_count=count)
        return

    expected = ["front", "front"]
    for pose in poses[1:]:
        expected.extend(["front", pose, pose])

    features = [sample[3] for sample in samples]
    ref_scores = [best_reference_score(references, feature, recognizer) for feature in features]

    front_scores = [ref_scores[index] for index, role in enumerate(expected) if role == "front"]
    front_anchors = [
        best_reference_score([features[0]], features[index], recognizer)
        for index, role in enumerate(expected)
        if index > 0 and role == "front"
    ]
    side_scores = [ref_scores[index] for index, role in enumerate(expected) if role in ("left", "right")]

    # Adjacent checks: held same-pose frames (e.g. front->front or left->left) must match strictly,
    # while cross-pose transitions (front<->side/pitch) avoid penalizing natural head turns when
    # enrolled references and front anchors already confirm identity.
    adjacent_scores = []
    cross_pose_margin = 0.04
    for index in range(1, count):
        adj_s = best_reference_score([features[index - 1]], features[index], recognizer)
        same_pose = expected[index] == expected[index - 1]
        adjacent_scores.append(adj_s if same_pose else min(1.0, adj_s + cross_pose_margin))

    # Enrollment captures front/left/right views but not up/down pitches; combine enrolled
    # similarity with continuous adjacent-frame transition while keeping a hard floor.
    pitch_floor = max(0.0, threshold - 0.05)
    pitch_scores = []
    for index, role in enumerate(expected):
        if role not in ("up", "down"):
            continue
        ref_s = ref_scores[index]
        adj_s = best_reference_score([features[index - 1]], features[index], recognizer)
        if ref_s < pitch_floor:
            pitch_scores.append(ref_s)
        else:
            pitch_scores.append(min(adj_s, 0.55 * ref_s + 0.45 * adj_s))

    score = min([*front_scores, *front_anchors, *side_scores, *adjacent_scores, *pitch_scores])
    # Leave a small rejection band above the ordinary matching threshold.
    matched = score >= min(1.0, threshold + 0.025)
    code = "MATCH" if matched else ("IDENTITY_UNCERTAIN" if score >= threshold else "BIOMETRIC_MISMATCH")
    result(matched, code, score, count, liveness_passed=True)


def verify_peer_snap(samples, challenge, references, recognizer, threshold, pad_threshold):
    count = len(samples)
    if count != 5:
        result(False, "INCOMPLETE_MULTI_FRAME_CAPTURE", frame_count=count, frame_consistent=False)
        return

    weakest_pad = min(sample[2] for sample in samples)
    if weakest_pad < pad_threshold:
        code = "LIVENESS_FAILED" if weakest_pad < 0.5 else "LIVENESS_UNCERTAIN"
        result(False, code, frame_count=count)
        return

    yaws = []
    boxes = []
    pixels = []
    features = []
    max_pixel_diff = 0.0
    for index, ((yaw, _pitch), box, _pad_score, feature, gray_thumb) in enumerate(samples):
        yaws.append(yaw)
        boxes.append(box)
        pixels.append(gray_thumb)
        features.append(feature)
        if index > 0:
            prev_box = boxes[index - 1]
            if math.hypot(box[0] - prev_box[0], box[1] - prev_box[1]) > 0.28:
                result(False, "LIVE_FRAME_INCONSISTENT", frame_count=count, frame_consistent=False)
                return
            if max(abs(box[i] - prev_box[i]) for i in (2, 3)) > 0.28:
                result(False, "LIVE_FRAME_INCONSISTENT", frame_count=count, frame_consistent=False)
                return
            diff = float(abs(gray_thumb - pixels[index - 1]).mean())
            if diff > max_pixel_diff:
                max_pixel_diff = diff

    # Require non-identical dynamic motion across the 5 frames (blocks static photo replay).
    if max_pixel_diff < 0.20:
        result(False, "LIVENESS_FAILED", frame_count=count, frame_consistent=False)
        return

    yaw_deltas = [y - yaws[0] for y in yaws]
    yaw_span = max(yaws) - min(yaws)
    if challenge == "turn_left" and max(yaw_deltas) < 0.08 and yaw_span < 0.10:
        result(False, "LIVE_POSE_MISMATCH", frame_count=count, frame_consistent=False)
        return
    if challenge == "turn_right" and min(yaw_deltas) > -0.08 and yaw_span < 0.10:
        result(False, "LIVE_POSE_MISMATCH", frame_count=count, frame_consistent=False)
        return

    adjacent_scores = [
        best_reference_score([features[i - 1]], features[i], recognizer)
        for i in range(1, count)
    ]
    if min(adjacent_scores) < max(0.24, threshold - 0.08):
        result(False, "LIVE_FRAME_INCONSISTENT", min(adjacent_scores), count, frame_consistent=False)
        return

    ref_scores = [best_reference_score(references, feature, recognizer) for feature in features]
    similarity = min(ref_scores)
    matched = similarity >= threshold
    code = "MATCH" if matched else "BIOMETRIC_MISMATCH"
    result(matched, code, similarity, count, frame_consistent=True, liveness_passed=True)


def main():
    if len(sys.argv) == 5 and sys.argv[1] == "--validate-profile":
        return validate_profile_image(sys.argv[2], sys.argv[3], sys.argv[4])

    if len(sys.argv) == 8 and sys.argv[1] == "--validate-views":
        return validate_views(sys.argv[2:5], sys.argv[5], sys.argv[6], sys.argv[7])

    arguments = sys.argv[1:]
    poses = None
    peer_challenge = None
    pad_model = None
    pad_threshold = None
    if arguments and arguments[0] == "--attendance":
        if len(arguments) < 4:
            result(False, "MATCHER_UNAVAILABLE")
            return 2
        poses = arguments[1].split(",")
        pad_model = arguments[2]
        try:
            pad_threshold = float(arguments[3])
        except ValueError:
            pad_threshold = 0
        if len(poses) != 5 or poses[0] != "front" or set(poses[1:]) != {"left", "right", "up", "down"} or not 0.5 < pad_threshold <= 1:
            result(False, "MATCHER_UNAVAILABLE")
            return 2
        arguments = arguments[4:]
    elif arguments and arguments[0] == "--peer-snap":
        if len(arguments) < 4:
            result(False, "MATCHER_UNAVAILABLE")
            return 2
        peer_challenge = arguments[1]
        pad_model = arguments[2]
        try:
            pad_threshold = float(arguments[3])
        except ValueError:
            pad_threshold = 0
        if peer_challenge not in ("blink_twice", "turn_left", "turn_right") or not 0.5 < pad_threshold <= 1:
            result(False, "MATCHER_UNAVAILABLE")
            return 2
        arguments = arguments[4:]
    reference_set = bool(arguments and arguments[0] == "--reference-set")
    if reference_set:
        arguments = arguments[1:]
    expected_arg_len = (18,) if poses else ((9,) if peer_challenge else (5, 6, 7))
    if len(arguments) not in expected_arg_len:
        result(False, "MATCHER_UNAVAILABLE")
        return 2

    frame_count = len(arguments) - 4
    reference_path = arguments[0]
    live_paths = arguments[1:1 + frame_count]
    detector_path = arguments[1 + frame_count]
    recognizer_path = arguments[2 + frame_count]
    threshold_text = arguments[3 + frame_count]
    try:
        threshold = float(threshold_text)
    except ValueError:
        result(False, "MATCHER_UNAVAILABLE")
        return 2

    if not math.isfinite(threshold) or not 0 < threshold <= 1:
        result(False, "MATCHER_UNAVAILABLE")
        return 2

    if not all(os.path.isfile(path) for path in (reference_path, *live_paths, detector_path, recognizer_path)):
        result(False, "MATCHER_UNAVAILABLE")
        return 2

    try:
        cv2.setNumThreads(1)
        detector = cv2.FaceDetectorYN.create(
            detector_path, "", (320, 320), score_threshold=0.9,
            nms_threshold=0.3, top_k=10
        )
        recognizer = cv2.FaceRecognizerSF.create(recognizer_path, "")
        if reference_set:
            with open(reference_path, encoding="utf-8") as manifest:
                reference_paths = json.load(manifest)
            if not isinstance(reference_paths, list) or not 1 <= len(reference_paths) <= 3:
                raise RuntimeError("Invalid reference set")
        else:
            reference_paths = [reference_path]
        references = [face_feature(path, detector, recognizer, "PROFILE")[0] for path in reference_paths]
        if poses:
            pad = cv2.dnn.readNetFromONNX(pad_model)
            samples = [attendance_sample(path, detector, recognizer, pad) for path in live_paths]
            verify_attendance(samples, poses, references, recognizer, threshold, pad_threshold)
            return 0
        if peer_challenge:
            pad = cv2.dnn.readNetFromONNX(pad_model)
            samples = [attendance_sample(path, detector, recognizer, pad) for path in live_paths]
            verify_peer_snap(samples, peer_challenge, references, recognizer, threshold, pad_threshold)
            return 0
        live_features = [face_feature(path, detector, recognizer, "LIVE") for path in live_paths]
        similarities = [
            best_reference_score(references, feature, recognizer)
            for feature, _ in live_features
        ]
        if not all(math.isfinite(value) and -1 <= value <= 1 for value in similarities):
            result(False, "MATCHER_UNAVAILABLE", 0.0, frame_count, False)
            return 2
        boxes = [box for _, box in live_features]
        frame_consistent = True
        for index, first in enumerate(boxes):
            for second in boxes[index + 1:]:
                center_distance = math.hypot(first[0] - second[0], first[1] - second[1])
                size_delta = max(abs(first[2] - second[2]), abs(first[3] - second[3]))
                if center_distance > 0.18 or size_delta > 0.20:
                    frame_consistent = False

        # Report the weakest sample, matching the all-frames-must-pass decision.
        # An average could hide one poor sample behind two stronger ones.
        similarity = min(similarities)
        if not frame_consistent:
            result(False, "LIVE_FRAME_INCONSISTENT", similarity, frame_count, False)
            return 0

        matched = all(value >= threshold for value in similarities)
        result(matched, "MATCH" if matched else "BIOMETRIC_MISMATCH", similarity, frame_count, True)
        return 0
    except ValueError as exc:
        result(False, str(exc))
        return 0
    except Exception:
        result(False, "MATCHER_UNAVAILABLE")
        return 2


if __name__ == "__main__":
    sys.exit(main())
