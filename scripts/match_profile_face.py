#!/usr/bin/env python3
"""Compare two server-held images with OpenCV Zoo YuNet and SFace."""

import json
import math
import os
import sys

try:
    import cv2
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

    Running the same restrained CLAHE transform on enrolled and live crops reduces
    sensitivity to shadows and uneven room lighting without relaxing the cosine
    match threshold. Chroma is left untouched to avoid shifting skin colors.
    """
    lab = cv2.cvtColor(aligned, cv2.COLOR_BGR2LAB)
    lightness, a_channel, b_channel = cv2.split(lab)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(4, 4))
    normalized_lightness = clahe.apply(lightness)
    normalized = cv2.merge((normalized_lightness, a_channel, b_channel))
    return cv2.cvtColor(normalized, cv2.COLOR_LAB2BGR)


def face_feature(image_path, detector, recognizer, kind, include_pose=False):
    image = cv2.imread(image_path, cv2.IMREAD_COLOR)
    if image is None:
        raise ValueError(f"{kind}_IMAGE_UNREADABLE")
    height, width = image.shape[:2]
    if min(width, height) < 100 or max(width, height) > 2000:
        raise ValueError(f"{kind}_IMAGE_QUALITY")

    detector.setInputSize((width, height))
    _, faces = detector.detect(image)
    if faces is None or len(faces) == 0:
        raise ValueError(f"{kind}_FACE_NOT_FOUND")
    if len(faces) != 1:
        raise ValueError(f"{kind}_MULTIPLE_FACES")

    face = faces[0]
    if face[2] < 60 or face[3] < 60:
        raise ValueError(f"{kind}_FACE_TOO_SMALL")

    aligned = recognizer.alignCrop(image, face)
    gray = cv2.cvtColor(aligned, cv2.COLOR_BGR2GRAY)
    if cv2.Laplacian(gray, cv2.CV_64F).var() < 20:
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
    detector.setInputSize((width, height))
    _, faces = detector.detect(image)
    if faces is None or len(faces) == 0:
        raise ValueError("LIVE_FACE_NOT_FOUND")
    if len(faces) != 1:
        raise ValueError("LIVE_MULTIPLE_FACES")

    face = faces[0]
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
    if cv2.Laplacian(gray, cv2.CV_64F).var() < 20:
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

    features = [sample[3] for sample in samples]
    scores = [best_reference_score(references, feature, recognizer) for feature in features]
    consistency = min(best_reference_score([features[0]], feature, recognizer) for feature in features[1:])
    score = min(min(scores), consistency)
    # Leave a small rejection band above the ordinary matching threshold.
    matched = score >= min(1.0, threshold + 0.025)
    code = "MATCH" if matched else ("IDENTITY_UNCERTAIN" if score >= threshold else "BIOMETRIC_MISMATCH")
    result(matched, code, score, count, liveness_passed=True)


def main():
    if len(sys.argv) == 5 and sys.argv[1] == "--validate-profile":
        return validate_profile_image(sys.argv[2], sys.argv[3], sys.argv[4])

    if len(sys.argv) == 8 and sys.argv[1] == "--validate-views":
        return validate_views(sys.argv[2:5], sys.argv[5], sys.argv[6], sys.argv[7])

    arguments = sys.argv[1:]
    poses = None
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
    reference_set = bool(arguments and arguments[0] == "--reference-set")
    if reference_set:
        arguments = arguments[1:]
    if len(arguments) not in ((18,) if poses else (5, 6, 7)):
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
