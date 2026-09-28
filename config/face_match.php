<?php

return [
    'python' => env('FACE_MATCH_PYTHON', '/usr/bin/python3'),
    'detector_model' => env('FACE_MATCH_DETECTOR_MODEL', '/opt/face-models/yunet.onnx'),
    'recognizer_model' => env('FACE_MATCH_RECOGNIZER_MODEL', '/opt/face-models/sface.onnx'),
];
