<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedClassesAndScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'name' => 'Jack C. Ole',
            'role' => 'student',
            'student_number' => '1234567',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_classes_and_schedule(): void
    {
        $this->get('/my-classes')->assertRedirect('/login');
        $this->get('/schedule')->assertRedirect('/login');
        $this->get('/my-classes/pdf')->assertRedirect('/login');
    }

    public function test_student_can_access_unified_classes_and_schedule_page(): void
    {
        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        $response->assertSee('Certificate of Registration');
        $response->assertSee('Classes & Schedule');
        $response->assertSee('Osmeña Colleges');
        $response->assertSee('Masbate City');
    }

    public function test_schedule_route_renders_unified_classes_and_schedule_page(): void
    {
        $response = $this->actingAs($this->student)->get('/schedule');

        $response->assertStatus(200);
        $response->assertSee('Certificate of Registration');
        $response->assertSee('Classes & Schedule');
    }

    public function test_page_displays_student_demographics_matching_cor_layout(): void
    {
        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        $response->assertSee('1234567');
        $response->assertSee('Fourth Year');
        $response->assertSee('Bachelor of Science in Computer Science');
        $response->assertSee('Jack C. Ole');
        $response->assertSee('Student Number');
        $response->assertSee('Year Level');
        $response->assertSee('Course');
        $response->assertSee('Name');
    }

    public function test_unified_schedule_table_displays_all_required_columns_and_subject_data(): void
    {
        // Teacher user
        $teacher = User::factory()->teacher()->create([
            'name' => 'Mr Leonardo Jr D. Ricalde',
        ]);

        $subject = Subject::create([
            'code' => 'GE 9ED',
            'name' => 'Life and Works of Rizal',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
            'units' => 3.0,
            'section' => '1 1423',
            'instructor' => 'Mr Leonardo Jr D. Ricalde',
            'instructor_id' => $teacher->id,
        ]);

        // Tuesday & Thursday schedule -> should format to TTH
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Tuesday',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'room' => '301',
        ]);

        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Thursday',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'room' => '301',
        ]);

        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);

        // Required COR table headers
        $response->assertSee('Section');
        $response->assertSee('Subject Code');
        $response->assertSee('Class');
        $response->assertSee('Units');
        $response->assertSee('Time');
        $response->assertSee('Day');
        $response->assertSee('Room');
        $response->assertSee("Teacher's Name", false);

        // Subject content
        $response->assertSee('1 1423');
        $response->assertSee('GE 9ED');
        $response->assertSee('Life and Works of Rizal');
        $response->assertSee('TTH');
        $response->assertSee('301');
        $response->assertSee('Mr Leonardo Jr D. Ricalde');
        $response->assertSee('3.0');
    }

    public function test_schedule_table_displays_total_units_aligned(): void
    {
        Subject::create([
            'code' => 'CS401',
            'name' => 'Advanced Database',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
            'units' => 3.0,
        ]);

        Subject::create([
            'code' => 'CS402',
            'name' => 'Software Engineering II',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
            'units' => 3.0,
        ]);

        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        $response->assertSee('Total Units');
        $response->assertSee('6.0');
    }

    public function test_cor_footer_sections_are_rendered(): void
    {
        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        $response->assertSee('Total Amount Paid');
        $response->assertSee('Scholarship');
        $response->assertSee('SUSAN I. AGUILAR');
        $response->assertSee('Registrar');
    }

    public function test_mobile_responsive_elements_are_rendered(): void
    {
        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        // Mobile view switcher
        $response->assertSee('cor-view-toggle-btns');
        $response->assertSee('corMobileCardsView');
        // Filter pills for quick scanning
        $response->assertSee('cor-day-pills-rail');
        $response->assertSee('scheduleSearchInput');
    }

    public function test_student_can_download_official_cor_pdf(): void
    {
        Subject::create([
            'code' => 'GE 9ED',
            'name' => 'Life and Works of Rizal',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
            'units' => 3.0,
        ]);

        $response = $this->actingAs($this->student)->get('/my-classes/pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
