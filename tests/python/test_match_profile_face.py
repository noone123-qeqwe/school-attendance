"""Model-independent checks for enrollment and attendance decision rules.

The detector/recognizer are faked here. These tests do not measure biometric
accuracy, presentation-attack resistance, or real-camera acceptance rates.
"""

import contextlib
import importlib.util
import io
import json
import math
from pathlib import Path
import sys
import tempfile
import types
import unittest
from unittest.mock import Mock, patch


fake_cv2 = types.ModuleType("cv2")
fake_cv2.FaceDetectorYN = types.SimpleNamespace(create=Mock())
fake_cv2.FaceRecognizerSF = types.SimpleNamespace(create=Mock())
fake_cv2.FaceRecognizerSF_FR_COSINE = 0
spec = importlib.util.spec_from_file_location(
    "face_matcher", Path(__file__).resolve().parents[2] / "scripts/match_profile_face.py"
)
matcher = importlib.util.module_from_spec(spec)
with patch.dict(sys.modules, {"cv2": fake_cv2}):
    spec.loader.exec_module(matcher)


class FaceEnrollmentRulesTest(unittest.TestCase):
    def test_front_and_opposite_turns_accept_either_camera_orientation(self):
        matcher.validate_pose_offsets([0.02, 0.23, -0.19])
        matcher.validate_pose_offsets([0.02, -0.19, 0.23])

    def test_duplicate_small_or_same_direction_turns_are_rejected(self):
        for offsets in ([0, 0, 0], [0, 0.09, -0.1], [0, 0.3, 0.2], [0, 0.9, -0.2], [0, math.nan, 0.3]):
            with self.subTest(offsets=offsets), self.assertRaisesRegex(ValueError, "PROFILE_POSE_VARIATION"):
                matcher.validate_pose_offsets(offsets)
        with self.assertRaisesRegex(ValueError, "PROFILE_POSE_FRONT"):
            matcher.validate_pose_offsets([0.3, 0.5, -0.2])

    def test_head_tilt_translation_and_scaling_do_not_create_a_false_turn(self):
        # Eyes at (0, 0), (100, 0); nose on their perpendicular bisector.
        for angle in (0, 0.4, -0.4):
            face = [0.0] * 15
            for index, x, y in ((4, 0, 0), (6, 100, 0), (8, 50, 50)):
                face[index] = 2 * (x * math.cos(angle) - y * math.sin(angle)) + 250
                face[index + 1] = 2 * (x * math.sin(angle) + y * math.cos(angle)) + 200
            self.assertAlmostEqual(0, matcher.pose_offset(face), places=6)
        face[8] = math.nan
        with self.assertRaisesRegex(ValueError, "PROFILE_IMAGE_QUALITY"):
            matcher.pose_offset(face)

    def enrollment(self, *, offsets=(0, 0.25, -0.25), scores=(0.8, 0.8, 0.8), feature_error=None):
        recognizer = Mock()
        recognizer.match.side_effect = scores
        captures = [(index, (0.5, 0.5, 0.3, 0.3), offset) for index, offset in enumerate(offsets)]
        output = io.StringIO()
        with patch.object(matcher.os.path, "isfile", return_value=True), \
                patch.object(fake_cv2.FaceRecognizerSF, "create", return_value=recognizer), \
                patch.object(matcher, "face_feature", side_effect=feature_error or captures), \
                contextlib.redirect_stdout(output):
            exit_code = matcher.validate_views(["front", "left", "right"], "detector", "recognizer", "0.363")
        return exit_code, json.loads(output.getvalue()), recognizer

    def test_all_three_pairs_must_match_before_enrollment(self):
        code, result, recognizer = self.enrollment()
        self.assertEqual(0, code)
        self.assertTrue(result["valid"])
        self.assertEqual(3, recognizer.match.call_count)
        code, result, _ = self.enrollment(scores=(0.8, 0.8, 0.2))
        self.assertEqual(0, code)
        self.assertEqual("PROFILE_IDENTITY_MISMATCH", result["code"])
        self.assertFalse(result["valid"])

    def test_pose_and_face_quality_failures_cannot_enroll(self):
        for options, expected in (({"offsets": (0, 0, 0)}, "PROFILE_POSE_VARIATION"),
                                  ({"feature_error": ValueError("PROFILE_MULTIPLE_FACES")}, "PROFILE_MULTIPLE_FACES")):
            with self.subTest(options=options):
                _, result, _ = self.enrollment(**options)
                self.assertFalse(result["valid"])
                self.assertEqual(expected, result["code"])

    def test_invalid_model_scores_fail_closed(self):
        for score in (math.nan, math.inf, 1.01, -1.01):
            with self.subTest(score=score):
                code, result, _ = self.enrollment(scores=(score,))
                self.assertEqual(2, code)
                self.assertEqual("MATCHER_UNAVAILABLE", result["code"])
                self.assertFalse(result["valid"])


class MultiReferenceAttendanceTest(unittest.TestCase):
    def decision(self, live_scores, boxes=None):
        with tempfile.TemporaryDirectory(dir=Path(__file__).parent) as directory:
            root = Path(directory)
            references = [str(root / pose) for pose in ("front", "left", "right")]
            frames = [str(root / "live1"), str(root / "live2")]
            models = [str(root / "detector"), str(root / "recognizer")]
            manifest = root / "manifest.json"
            manifest.write_text(json.dumps(references), encoding="utf-8")
            for path in references + frames + models:
                Path(path).touch()
            standard_box = (0.5, 0.5, 0.3, 0.3)
            def features(path, detector, recognizer, kind):
                box = (boxes or {}).get(path.rsplit("live", 1)[-1], standard_box)
                return path, box
            recognizer = Mock()
            recognizer.match.side_effect = lambda reference, live, kind: live_scores[frames.index(live)][references.index(reference)]
            output = io.StringIO()
            with patch.object(matcher.sys, "argv", ["matcher", "--reference-set", str(manifest), *frames, *models, "0.363"]), \
                    patch.object(fake_cv2.FaceRecognizerSF, "create", return_value=recognizer), \
                    patch.object(matcher, "face_feature", side_effect=features), contextlib.redirect_stdout(output):
                exit_code = matcher.main()
            return exit_code, json.loads(output.getvalue())

    def test_each_live_frame_can_match_a_different_enrolled_view(self):
        code, result = self.decision([[0.2, 0.7, 0.3], [0.2, 0.3, 0.6]])
        self.assertEqual(0, code)
        self.assertTrue(result["match"])
        self.assertEqual(0.6, result["similarity"])
        self.assertEqual(2, result["frame_count"])

    def test_a_strong_sample_cannot_hide_one_failed_sample(self):
        _, result = self.decision([[0.9, 0.8, 0.7], [0.1, 0.2, 0.3]])
        self.assertFalse(result["match"])
        self.assertEqual("BIOMETRIC_MISMATCH", result["code"])
        self.assertEqual(0.3, result["similarity"])

    def test_existing_live_frame_consistency_rule_is_preserved(self):
        _, result = self.decision([[0.8] * 3, [0.8] * 3], boxes={"2": (0.9, 0.5, 0.3, 0.3)})
        self.assertFalse(result["match"])
        self.assertEqual("LIVE_FRAME_INCONSISTENT", result["code"])

    def test_invalid_reference_comparison_cannot_be_hidden_by_a_good_score(self):
        code, result = self.decision([[0.8, math.nan, 0.8], [0.8] * 3])
        self.assertEqual(2, code)
        self.assertFalse(result["match"])
        self.assertEqual("MATCHER_UNAVAILABLE", result["code"])


if __name__ == "__main__":
    unittest.main()
