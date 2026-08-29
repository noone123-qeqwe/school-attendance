<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $student = User::factory()->create([
            'role' => 'student',
            'profile_image' => null,
        ]);

        $file = UploadedFile::fake()->create('avatar.jpg', 1500, 'image/jpeg');

        $response = $this->actingAs($student)->post(route('profile.image.update'), [
            'profile_image' => $file,
        ]);

        $response->assertSessionHas('success');
        $student->refresh();
        $this->assertNotNull($student->profile_image);
        Storage::disk('public')->assertExists($student->profile_image);
    }

    public function test_teacher_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'profile_image' => null,
        ]);

        $file = UploadedFile::fake()->create('teacher_avatar.png', 3000, 'image/png');

        $response = $this->actingAs($teacher)->post(route('teacher.profile.image'), [
            'profile_image' => $file,
        ]);

        $response->assertSessionHas('success');
        $teacher->refresh();
        $this->assertNotNull($teacher->profile_image);
        Storage::disk('public')->assertExists($teacher->profile_image);
    }

    public function test_admin_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
            'profile_image' => null,
        ]);

        $file = UploadedFile::fake()->create('admin_avatar.webp', 4000, 'image/webp');

        $response = $this->actingAs($admin)->post(route('admin.profile.image'), [
            'profile_image' => $file,
        ]);

        $response->assertSessionHas('success');
        $admin->refresh();
        $this->assertNotNull($admin->profile_image);
        Storage::disk('public')->assertExists($admin->profile_image);
    }

    public function test_parent_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $parent = User::factory()->create([
            'role' => 'parent',
            'profile_image' => null,
        ]);

        $file = UploadedFile::fake()->create('parent_avatar.jpg', 2000, 'image/jpeg');

        $response = $this->actingAs($parent)->post(route('parent.profile.update'), [
            'name' => 'Updated Parent Name',
            'profile_image' => $file,
        ]);

        $response->assertSessionHas('success');
        $parent->refresh();
        $this->assertNotNull($parent->profile_image);
        Storage::disk('public')->assertExists($parent->profile_image);
    }

    public function test_oversized_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $student = User::factory()->create(['role' => 'student']);

        // 12 MB (exceeds 10MB limit)
        $oversized = UploadedFile::fake()->create('large.jpg', 12000, 'image/jpeg');

        $response = $this->actingAs($student)->post(route('profile.image.update'), [
            'profile_image' => $oversized,
        ]);

        $response->assertSessionHasErrors('profile_image');
    }
}
