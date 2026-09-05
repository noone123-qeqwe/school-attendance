<?php

namespace App\Console\Commands;

use App\Services\Email\EmailDeliveryService;
use Illuminate\Console\Command;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:test 
                            {email : The destination email address to send the diagnostic email to}';

    /**
     * The console command description.
     */
    protected $description = 'Send an independent diagnostic test email through the configured mail provider to verify delivery pipeline';

    /**
     * Execute the console command.
     */
    public function handle(EmailDeliveryService $emailDeliveryService): int
    {
        $recipient = trim((string) $this->argument('email'));

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: '{$recipient}'");
            return self::FAILURE;
        }

        $this->info("==================================================");
        $this->info("  EMAIL DELIVERY PIPELINE DIAGNOSTIC TEST");
        $this->info("==================================================");
        $this->line("Recipient:         <fg=cyan>{$recipient}</>");
        $this->line("Active Provider:   <fg=yellow>" . $emailDeliveryService->getActiveProviderName() . "</>");
        $this->line("Default Mailer:    <fg=yellow>" . config('mail.default') . "</>");
        $this->line("From Address:      <fg=yellow>" . config('mail.from.address') . "</>");
        $this->line("From Name:         <fg=yellow>" . config('mail.from.name') . "</>");
        $this->line("Initiating test transmission...");

        $start = microtime(true);
        $result = $emailDeliveryService->sendDiagnosticTestEmail($recipient);
        $duration = round((microtime(true) - $start) * 1000, 2);

        $this->newLine();
        if ($result->success) {
            $this->info("✔ SUCCESS: Email accepted by provider!");
            $this->table(
                ['Metric', 'Detail'],
                [
                    ['Provider', $result->provider],
                    ['Provider Accepted', 'YES'],
                    ['Provider Message ID', $result->messageId ?: 'N/A'],
                    ['Provider Response', $result->response ?: '250 OK'],
                    ['Round-trip Latency', "{$duration} ms"],
                    ['Delivery Status', 'ACCEPTED / HANDED OFF'],
                ]
            );
            $this->info("Please check {$recipient} (Inbox, Spam, Promotions).");
            return self::SUCCESS;
        }

        $this->error("✖ FAILURE: Email delivery failed!");
        $this->table(
            ['Metric', 'Detail'],
            [
                ['Provider', $result->provider],
                ['Provider Accepted', 'NO'],
                ['Error', $result->error ?: 'Unknown error'],
                ['Round-trip Latency', "{$duration} ms"],
                ['Delivery Status', 'REJECTED / FAILED'],
            ]
        );
        $this->line("<fg=yellow>Troubleshooting:</> Check your MAIL_HOST, MAIL_PORT, MAIL_USERNAME, and MAIL_PASSWORD in your .env or Render environment.");
        return self::FAILURE;
    }
}
