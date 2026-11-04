<?php

namespace Tests\Unit;

use Tests\TestCase;

class FaceMatchThresholdConfigTest extends TestCase
{
    public function test_peer_snap_threshold_is_independent_of_qr_sface_threshold(): void
    {
        $keys = ['FACE_MATCH_THRESHOLD', 'PEER_FACE_MATCH_THRESHOLD'];
        $previous = [];

        foreach ($keys as $key) {
            $previous[$key] = [
                'env_exists' => array_key_exists($key, $_ENV),
                'env' => $_ENV[$key] ?? null,
                'server_exists' => array_key_exists($key, $_SERVER),
                'server' => $_SERVER[$key] ?? null,
                'process' => getenv($key),
            ];
        }

        try {
            $_ENV['FACE_MATCH_THRESHOLD'] = '0.363';
            $_SERVER['FACE_MATCH_THRESHOLD'] = '0.363';
            putenv('FACE_MATCH_THRESHOLD=0.363');

            unset($_ENV['PEER_FACE_MATCH_THRESHOLD'], $_SERVER['PEER_FACE_MATCH_THRESHOLD']);
            putenv('PEER_FACE_MATCH_THRESHOLD');

            $peerConfig = require config_path('peer_snap.php');

            $this->assertSame(0.85, $peerConfig['match_threshold']);
        } finally {
            foreach ($keys as $key) {
                if ($previous[$key]['env_exists']) {
                    $_ENV[$key] = $previous[$key]['env'];
                } else {
                    unset($_ENV[$key]);
                }

                if ($previous[$key]['server_exists']) {
                    $_SERVER[$key] = $previous[$key]['server'];
                } else {
                    unset($_SERVER[$key]);
                }

                if ($previous[$key]['process'] === false) {
                    putenv($key);
                } else {
                    putenv($key . '=' . $previous[$key]['process']);
                }
            }
        }
    }
}
