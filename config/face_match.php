<?php

return [
    'python' => env('FACE_MATCH_PYTHON', '/usr/bin/python3'),
    'detector_model' => env('FACE_MATCH_DETECTOR_MODEL', '/opt/face-models/yunet.onnx'),
    'recognizer_model' => env('FACE_MATCH_RECOGNIZER_MODEL', '/opt/face-models/sface.onnx'),
    'attendance_pad_model' => env('FACE_ATTENDANCE_PAD_MODEL', '/opt/face-models/anti-spoof-mn3.onnx'),
    'attendance_enabled' => (bool) env('FACE_ATTENDANCE_LIVENESS_ENABLED', false),
    'attendance_pad_threshold' => env('FACE_ATTENDANCE_PAD_THRESHOLD', 0.90),
    'threshold' => (function () {
        $threshold = env('FACE_MATCH_THRESHOLD');
        return $threshold === null || $threshold === '' ? 0.363 : $threshold;
    })(),
];
