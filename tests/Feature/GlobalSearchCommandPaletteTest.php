<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\ParentNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GlobalSearchCommandPaletteTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' Search User',
            'student_number' => $role === 'student' ? '2026-CMD-1' : null,
            'employee_id' => $role !== 'student' ? strtoupper($role) . '-CMD-1' : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '1',
            'semester' => '1',
            'email' => $role . '.cmd@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    public function test_student_command_palette_uses_locked_height_wrapping_text_and_dynamic_labels(): void
    {
        $student = $this->createUser('student');

        $html = $this->actingAs($student)->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('css/student-frontend.css', $html);
        $this->assertStringContainsString('js/student-frontend.js', $html);
        $this->assertStringContainsString('id="headerSearchBar"', $html);
        $this->assertStringContainsString('id="cmdPaletteOverlay"', $html);
        $this->assertStringContainsString('id="cmdPaletteInput"', $html);
        $this->assertStringContainsString('id="cmdPaletteItems"', $html);
        $this->assertStringContainsString('window.__SF_COMMANDS__', $html);

        $frontendCss = file_get_contents(public_path('css/student-frontend.css'));
        $this->assertIsString($frontendCss);
        $this->assertStringContainsString('.sf-cmd-card', $frontendCss);
        $this->assertStringContainsString('.sf-cmd-list', $frontendCss);
        $this->assertStringContainsString('overflow-y: auto;', $frontendCss);
    }

    public function test_admin_and_parent_search_items_populate_all_command_palette_categories(): void
    {
        $admin = $this->createUser('admin', ['admin_sub_role' => 'super_admin']);
        $adminCategories = array_values(array_unique(array_column(AdminNavigation::searchItems($admin), 'category')));
        sort($adminCategories);
        $this->assertSame(['academic', 'action', 'navigation', 'system'], $adminCategories);

        $parentCategories = array_values(array_unique(array_column(ParentNavigation::searchItems(null), 'category')));
        sort($parentCategories);
        $this->assertSame(['academic', 'action', 'navigation', 'system'], $parentCategories);
    }

    public function test_teacher_parent_and_admin_dashboards_render_consistent_search_palette(): void
    {
        $teacher = $this->createUser('teacher');
        $parent = $this->createUser('parent');
        $admin = $this->createUser('admin', ['admin_sub_role' => 'super_admin']);

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('id="cmdPaletteGroupLabel"', false)
            ->assertSee('id="cmdPaletteBody"', false);

        $this->flushSession();

        $this->actingAs($parent)->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('id="cmdPaletteGroupLabel"', false)
            ->assertSee('syncViewportMetrics', false);

        $this->flushSession();

        $this->actingAs($admin)
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="cmdPaletteGroupLabel"', false)
            ->assertSee('id="cmdEmptyActions"', false);
    }
}
