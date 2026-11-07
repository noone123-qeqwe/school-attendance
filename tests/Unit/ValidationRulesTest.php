<?php

namespace Tests\Unit;

use App\Rules\ValidCoordinate;
use App\Rules\ValidAcademicYear;
use App\Rules\ValidPhoneNumber;
use App\Rules\ValidStudentId;
use PHPUnit\Framework\TestCase;

class ValidationRulesTest extends TestCase
{
    // --- ValidStudentId ---

    public function test_valid_generated_student_id_is_accepted(): void
    {
        $this->assertTrue(ValidStudentId::isValid('2600001'));
    }

    public function test_student_id_must_be_seven_digits_with_nonzero_sequence(): void
    {
        $this->assertFalse(ValidStudentId::isValid('260001'));
        $this->assertFalse(ValidStudentId::isValid('26ABC01'));
        $this->assertFalse(ValidStudentId::isValid('2600000'));
    }

    // --- ValidCoordinate ---

    public function test_valid_latitude(): void
    {
        $rule = ValidCoordinate::latitude();
        $failed = false;
        $rule->validate('lat', 14.5995, function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    public function test_valid_latitude_negative(): void
    {
        $rule = ValidCoordinate::latitude();
        $failed = false;
        $rule->validate('lat', -33.8688, function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    public function test_invalid_latitude_too_high(): void
    {
        $rule = ValidCoordinate::latitude();
        $failed = false;
        $rule->validate('lat', 91.0, function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    public function test_invalid_latitude_too_low(): void
    {
        $rule = ValidCoordinate::latitude();
        $failed = false;
        $rule->validate('lat', -91.0, function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    public function test_valid_longitude(): void
    {
        $rule = ValidCoordinate::longitude();
        $failed = false;
        $rule->validate('lng', 120.9842, function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    public function test_invalid_longitude_too_high(): void
    {
        $rule = ValidCoordinate::longitude();
        $failed = false;
        $rule->validate('lng', 181.0, function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    public function test_non_numeric_coordinate_fails(): void
    {
        $rule = ValidCoordinate::latitude();
        $failed = false;
        $rule->validate('lat', 'abc', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    // --- ValidPhoneNumber ---

    public function test_valid_ph_number_09(): void
    {
        $rule = new ValidPhoneNumber();
        $failed = false;
        $rule->validate('phone', '09171234567', function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    public function test_valid_ph_number_plus63(): void
    {
        $rule = new ValidPhoneNumber();
        $failed = false;
        $rule->validate('phone', '+639171234567', function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    public function test_phone_numbers_normalize_to_semaphore_format(): void
    {
        $this->assertSame('639171234567', ValidPhoneNumber::normalize('0917 123 4567'));
        $this->assertSame('639171234567', ValidPhoneNumber::normalize('+63 (917) 123-4567'));
        $this->assertSame('639171234567', ValidPhoneNumber::normalize('639171234567'));
    }

    public function test_valid_ph_number_63(): void
    {
        $rule = new ValidPhoneNumber();
        $failed = false;
        $rule->validate('phone', '639171234567', function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    public function test_invalid_ph_number_too_short(): void
    {
        $rule = new ValidPhoneNumber();
        $failed = false;
        $rule->validate('phone', '0917123', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    public function test_invalid_ph_number_wrong_prefix(): void
    {
        $rule = new ValidPhoneNumber();
        $failed = false;
        $rule->validate('phone', '1234567890', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    public function test_invalid_ph_number_with_letters(): void
    {
        $rule = new ValidPhoneNumber();
        $failed = false;
        $rule->validate('phone', '0917abc1234', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    // --- ValidAcademicYear ---

    public function test_valid_academic_year_and_date_range_are_accepted(): void
    {
        $rule = new ValidAcademicYear('2025-08-01', '2026-05-31');
        $failed = false;
        $rule->validate('name', '2025-2026', function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    public function test_invalid_academic_year_label_is_rejected(): void
    {
        $rule = new ValidAcademicYear('2025-08-01', '2026-05-31');
        $failed = false;
        $rule->validate('name', '2025/2026', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    public function test_academic_year_must_match_dates_and_be_consecutive(): void
    {
        $rule = new ValidAcademicYear('2025-08-01', '2025-09-01');
        $failed = false;
        $rule->validate('name', '2025-2027', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    public function test_academic_year_allows_a_single_term_within_the_first_named_year(): void
    {
        $rule = new ValidAcademicYear('2026-08-01', '2026-12-31');
        $failed = false;
        $rule->validate('name', '2026-2027', function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }
}
