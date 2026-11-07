<?php

namespace Tests\Unit;

use App\Services\OtpService;
use PHPUnit\Framework\TestCase;

class OtpServiceTest extends TestCase
{
    // --- maskEmail ---

    public function test_mask_email_standard(): void
    {
        $this->assertEquals('j*****@gmail.com', OtpService::maskEmail('john.doe@gmail.com'));
    }

    public function test_mask_email_single_char_username(): void
    {
        $this->assertEquals('a*****@gmail.com', OtpService::maskEmail('a@gmail.com'));
    }

    public function test_mask_email_invalid(): void
    {
        $this->assertEquals('***', OtpService::maskEmail('not-an-email'));
    }

    public function test_mask_email_preserves_domain(): void
    {
        $result = OtpService::maskEmail('student@school.edu');
        $this->assertStringEndsWith('@school.edu', $result);
    }

    public function test_mask_email_trims_whitespace(): void
    {
        $this->assertEquals('t*****@gmail.com', OtpService::maskEmail('  test@gmail.com  '));
    }

    // --- maskIp ---

    public function test_mask_ipv4(): void
    {
        $this->assertEquals('192.168.***.***', OtpService::maskIp('192.168.1.100'));
    }

    public function test_mask_ipv6(): void
    {
        $result = OtpService::maskIp('2001:db8::1');
        $this->assertStringContainsString('2001', $result);
        $this->assertStringContainsString('****', $result);
    }

    public function test_mask_ip_null(): void
    {
        $this->assertEquals('unknown', OtpService::maskIp(null));
    }

    public function test_mask_ip_unknown(): void
    {
        $this->assertEquals('unknown', OtpService::maskIp('unknown'));
    }

    // --- isValidGmailFormat ---

    public function test_valid_gmail(): void
    {
        $this->assertTrue(OtpService::isValidGmailFormat('testuser@gmail.com', true));
    }

    public function test_valid_gmail_with_dots(): void
    {
        $this->assertTrue(OtpService::isValidGmailFormat('test.user.name@gmail.com', true));
    }

    public function test_valid_googlemail(): void
    {
        $this->assertTrue(OtpService::isValidGmailFormat('testuser@googlemail.com', true));
    }

    public function test_invalid_non_gmail_domain(): void
    {
        $this->assertFalse(OtpService::isValidGmailFormat('user@yahoo.com', true));
    }

    public function test_invalid_short_username(): void
    {
        // Less than 6 chars after removing dots
        $this->assertFalse(OtpService::isValidGmailFormat('ab@gmail.com', true));
    }

    public function test_invalid_leading_dot(): void
    {
        $this->assertFalse(OtpService::isValidGmailFormat('.testuser@gmail.com', true));
    }

    public function test_invalid_trailing_dot(): void
    {
        $this->assertFalse(OtpService::isValidGmailFormat('testuser.@gmail.com', true));
    }

    public function test_invalid_consecutive_dots(): void
    {
        $this->assertFalse(OtpService::isValidGmailFormat('test..user@gmail.com', true));
    }

    public function test_invalid_special_chars(): void
    {
        $this->assertFalse(OtpService::isValidGmailFormat('test+user@gmail.com', true));
    }

    public function test_case_insensitive(): void
    {
        $this->assertTrue(OtpService::isValidGmailFormat('TestUser@Gmail.Com', true));
    }
}
