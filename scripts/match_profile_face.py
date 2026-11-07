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


def result(match, code, similarity=0.0, frame_count=1, frame_consistent=True):
    print(json.dumps({
        "match": match,
        "code": code,
        "similarity": round(float(similarity), 4),
        "frame_count": frame_count,
        "frame_consistent": frame_consistent,
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


def face_feature(image_path, detector, recognizer, kind):
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

    feature = recognizer.feature(aligned)
    box = (
        float(face[0] + face[2] / 2) / width,
        float(face[1] + face[3] / 2) / height,
        float(face[2]) / width,
        float(face[3]) / height,
    )

    return feature, box


def main():
    if len(sys.argv) == 5 and sys.argv[1] == "--validate-profile":
        return validate_profile_image(sys.argv[2], sys.argv[3], sys.argv[4])

    if len(sys.argv) not in (6, 7, 8):
        result(False, "MATCHER_UNAVAILABLE")
        return 2

    frame_count = len(sys.argv) - 5
    reference_path = sys.argv[1]
    live_paths = sys.argv[2:2 + frame_count]
    detector_path = sys.argv[2 + frame_count]
    recognizer_path = sys.argv[3 + frame_count]
    threshold_text = sys.argv[4 + frame_count]
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
        detector = cv2.FaceDetectorYN.create(
            detector_path, "", (320, 320), score_threshold=0.9,
            nms_threshold=0.3, top_k=10
        )
        recognizer = cv2.FaceRecognizerSF.create(recognizer_path, "")
        reference, _ = face_feature(reference_path, detector, recognizer, "PROFILE")
        live_features = [face_feature(path, detector, recognizer, "LIVE") for path in live_paths]
        similarities = [
            float(recognizer.match(reference, feature, cv2.FaceRecognizerSF_FR_COSINE))
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
