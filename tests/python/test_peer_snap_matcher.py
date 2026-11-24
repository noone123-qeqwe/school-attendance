import io
import json
import sys
import unittest
from contextlib import redirect_stdout
from unittest import mock

# Stub cv2 cleanly before importing match_profile_face on hosts without native OpenCV shared libraries
if "cv2" not in sys.modules:
    sys.modules["cv2"] = mock.MagicMock()

import scripts.match_profile_face as matcher


class DummyPixels:
    def __init__(self, values):
        self.values = values

    def __sub__(self, other):
        diffs = [abs(a - b) for a, b in zip(self.values, other.values)]
        return DummyArray(diffs)


class DummyArray:
    def __init__(self, diffs):
        self._diffs = diffs

    def __abs__(self):
        return self

    def mean(self):
        return sum(self._diffs) / len(self._diffs) if self._diffs else 0.0


class PeerSnapMatcherTests(unittest.TestCase):
    def _run_verify(self, samples, challenge, scores, pad_threshold=0.90, threshold=0.363):
        recognizer = mock.MagicMock()
        recognizer.match.side_effect = scores
        buf = io.StringIO()
        with redirect_stdout(buf):
            matcher.verify_peer_snap(samples, challenge, ["ref1"], recognizer, threshold, pad_threshold)
        return json.loads(buf.getvalue().strip())

    def test_rejects_low_liveness_score(self):
        pixels = [DummyPixels([10.0 * i, 12.0 * i]) for i in range(5)]
        samples = [
            ((0.0, 0.5), (0.5, 0.5, 0.3, 0.4), 0.95 if i < 4 else 0.30, f"f{i}", pixels[i])
            for i in range(5)
        ]
        res = self._run_verify(samples, "blink_twice", [])
        self.assertFalse(res["match"])
        self.assertEqual(res["code"], "LIVENESS_FAILED")

    def test_rejects_static_replayed_frames(self):
        identical = DummyPixels([100.0, 100.0])
        samples = [((0.0, 0.5), (0.5, 0.5, 0.3, 0.4), 0.96, f"f{i}", identical) for i in range(5)]
        res = self._run_verify(samples, "blink_twice", [])
        self.assertFalse(res["match"])
        self.assertEqual(res["code"], "LIVENESS_FAILED")

    def test_rejects_turn_left_without_yaw_motion(self):
        samples = [
            ((0.01 * i, 0.5), (0.5, 0.5, 0.3, 0.4), 0.95, f"f{i}", DummyPixels([float(i * 10), float(i * 5)]))
            for i in range(5)
        ]
        res = self._run_verify(samples, "turn_left", [])
        self.assertFalse(res["match"])
        self.assertEqual(res["code"], "LIVE_POSE_MISMATCH")

    def test_accepts_valid_live_turn_left_match(self):
        yaws = [0.0, 0.05, 0.14, 0.19, 0.12]
        samples = [
            ((yaws[i], 0.5), (0.5, 0.5, 0.3, 0.4), 0.96, f"f{i}", DummyPixels([float(i * 10), float(i * 5)]))
            for i in range(5)
        ]
        # 4 adjacent scores + 5 reference scores
        scores = [0.60, 0.62, 0.59, 0.61, 0.50, 0.48, 0.52, 0.51, 0.49]
        res = self._run_verify(samples, "turn_left", scores)
        self.assertTrue(res["match"])
        self.assertEqual(res["code"], "MATCH")
        self.assertTrue(res["liveness_passed"])
        self.assertEqual(res["frame_count"], 5)


if __name__ == "__main__":
    unittest.main()
