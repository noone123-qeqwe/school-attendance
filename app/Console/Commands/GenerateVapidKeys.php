<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:vapid {--show : Display keys only without modifying .env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate standard VAPID public and private keys for Web Push Notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating VAPID cryptographic keypair (NIST P-256 / prime256v1 curve)...');

        $publicKey = null;
        $privateKey = null;

        // Auto-locate OpenSSL config on Windows if needed
        $opensslCnf = null;
        $candidatePaths = [
            getenv('OPENSSL_CONF'),
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:/xampp/apache/bin/openssl.cnf',
            'C:/php/extras/ssl/openssl.cnf',
            dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',
        ];

        foreach ($candidatePaths as $path) {
            if ($path && file_exists($path)) {
                $opensslCnf = $path;
                putenv("OPENSSL_CONF={$path}");
                break;
            }
        }

        if (class_exists(\Minishlink\WebPush\VAPID::class)) {
            try {
                $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
                $publicKey = $keys['publicKey'];
                $privateKey = $keys['privateKey'];
            } catch (\Throwable $e) {
                $this->warn('Minishlink VAPID helper notice: ' . $e->getMessage() . '. Falling back to native OpenSSL engine.');
            }
        }

        if (!$publicKey || !$privateKey) {
            try {
                $config = [
                    'curve_name' => 'prime256v1',
                    'private_key_type' => OPENSSL_KEYTYPE_EC,
                ];

                if ($opensslCnf) {
                    $config['config'] = $opensslCnf;
                }

                $res = openssl_pkey_new($config);
                if (!$res) {
                    throw new \Exception('OpenSSL failed to generate EC key: ' . openssl_error_string());
                }

                $details = openssl_pkey_get_details($res);
                if (!isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
                    throw new \Exception('Unable to extract EC curve coordinates.');
                }

                // NIST P-256 uncompressed point representation (0x04 + X + Y)
                $publicKeyRaw = "\x04" . $details['ec']['x'] . $details['ec']['y'];
                $publicKey = rtrim(strtr(base64_encode($publicKeyRaw), '+/', '-_'), '=');
                $privateKey = rtrim(strtr(base64_encode($details['ec']['d']), '+/', '-_'), '=');
            } catch (\Throwable $e) {
                $this->error('Failed generating VAPID keys: ' . $e->getMessage());
                return 1;
            }
        }

        $this->line('');
        $this->info('✓ VAPID Keys Generated Successfully!');
        $this->table(
            ['Key', 'Value'],
            [
                ['VAPID_PUBLIC_KEY', $publicKey],
                ['VAPID_PRIVATE_KEY', $privateKey],
            ]
        );

        if ($this->option('show')) {
            return 0;
        }

        $envFile = base_path('.env');
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);

            // Update or append VAPID_PUBLIC_KEY
            if (preg_match('/^VAPID_PUBLIC_KEY=.*$/m', $envContent)) {
                $envContent = preg_replace('/^VAPID_PUBLIC_KEY=.*$/m', 'VAPID_PUBLIC_KEY=' . $publicKey, $envContent);
            } else {
                $envContent .= "\nVAPID_PUBLIC_KEY=" . $publicKey;
            }

            // Update or append VAPID_PRIVATE_KEY
            if (preg_match('/^VAPID_PRIVATE_KEY=.*$/m', $envContent)) {
                $envContent = preg_replace('/^VAPID_PRIVATE_KEY=.*$/m', 'VAPID_PRIVATE_KEY=' . $privateKey, $envContent);
            } else {
                $envContent .= "\nVAPID_PRIVATE_KEY=" . $privateKey;
            }

            // Update or append VAPID_SUBJECT
            if (!preg_match('/^VAPID_SUBJECT=.*$/m', $envContent)) {
                $envContent .= "\nVAPID_SUBJECT=mailto:admin@school-attendance.edu";
            }

            file_put_contents($envFile, $envContent);
            $this->info('✓ Updated .env with generated VAPID keys.');
        }

        return 0;
    }
}
