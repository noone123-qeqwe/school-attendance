#!/usr/bin/env python3
"""Compare two server-held images with OpenCV Zoo YuNet and SFace."""

import json
import os
import sys

try:
    import cv2
except ImportError:
    print(json.dumps({"match": False, "code": "MATCHER_UNAVAILABLE"}))
    sys.exit(2)


def result(match, code, similarity=0.0):
    print(json.dumps({"match": match, "code": code, "similarity": round(float(similarity), 4)}))


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

    return recognizer.feature(aligned)


def main():
    if len(sys.argv) != 5:
        result(False, "MATCHER_UNAVAILABLE")
        return 2

    reference_path, live_path, detector_path, recognizer_path = sys.argv[1:]
    if not all(os.path.isfile(path) for path in sys.argv[1:]):
        result(False, "MATCHER_UNAVAILABLE")
        return 2

    try:
        detector = cv2.FaceDetectorYN.create(
            detector_path, "", (320, 320), score_threshold=0.9,
            nms_threshold=0.3, top_k=10
        )
        recognizer = cv2.FaceRecognizerSF.create(recognizer_path, "")
        reference = face_feature(reference_path, detector, recognizer, "PROFILE")
        live = face_feature(live_path, detector, recognizer, "LIVE")
        similarity = recognizer.match(reference, live, cv2.FaceRecognizerSF_FR_COSINE)
        # OpenCV Zoo's SFace cosine threshold for the released model is 0.363.
        result(similarity >= 0.363, "MATCH" if similarity >= 0.363 else "BIOMETRIC_MISMATCH", similarity)
        return 0
    except ValueError as exc:
        result(False, str(exc))
        return 0
    except Exception:
        result(False, "MATCHER_UNAVAILABLE")
        return 2


if __name__ == "__main__":
    sys.exit(main())
