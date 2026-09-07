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

        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400)->size(1500);

        $response = $this->actingAs($student)->post(route('profile.image.update'), [
            'profile_image' => $file,
        ]);

        $response->assertSessionHas('success');
        $student->refresh();
        $this->assertNotNull($student->profile_image);
        Storage::disk('public')->assertExists($student->profile_image);
    }

    public function test_student_ajax_upload_returns_json_and_versioned_url(): void
    {
        Storage::fake('public');

        $student = User::factory()->create([
            'role' => 'student',
            'profile_image' => null,
        ]);

        $file = UploadedFile::fake()->image('student_crop.png', 512, 512)->size(800);

        $response = $this->actingAs($student)->postJson(route('profile.image.update'), [
            'profile_image' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'image_url',
            'versioned_url',
            'has_custom',
        ]);
        $response->assertJson([
            'success' => true,
            'has_custom' => true,
        ]);

        $student->refresh();
        $this->assertNotNull($student->profile_image);
        $this->assertTrue($student->has_custom_profile_image);
    }

    public function test_teacher_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'profile_image' => null,
        ]);

        $file = UploadedFile::fake()->image('teacher_avatar.png', 500, 500)->size(2000);

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

        $file = UploadedFile::fake()->image('admin_avatar.webp', 600, 600)->size(3000);

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

        $file = UploadedFile::fake()->image('parent_avatar.jpg', 400, 400)->size(1200);

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

        // 6 MB (exceeds 5MB limit)
        $oversized = UploadedFile::fake()->image('large.jpg', 400, 400)->size(6144);

        $response = $this->actingAs($student)->post(route('profile.image.update'), [
            'profile_image' => $oversized,
        ]);

        $response->assertSessionHasErrors('profile_image');
    }

    public function test_non_image_file_is_rejected(): void
    {
        Storage::fake('public');

        $student = User::factory()->create(['role' => 'student']);

        $pdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($student)->postJson(route('profile.image.update'), [
            'profile_image' => $pdf,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('profile_image');
    }

    public function test_authenticated_user_can_delete_profile_image_and_restore_default(): void
    {
        Storage::fake('public');

        $storedPath = 'profile_images/test_avatar.jpg';
        Storage::disk('public')->put($storedPath, 'fake-image-contents');

        $user = User::factory()->create([
            'role' => 'student',
            'profile_image' => $storedPath,
        ]);

        $this->assertTrue($user->has_custom_profile_image);

        $response = $this->actingAs($user)->deleteJson(route('profile.image.delete'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'has_custom' => false,
        ]);

        $user->refresh();
        $this->assertNull($user->profile_image);
        $this->assertFalse($user->has_custom_profile_image);
        $this->assertStringContainsString('ui-avatars.com', $user->profile_photo_url);
        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_guest_cannot_upload_or_delete_profile_photo(): void
    {
        $file = UploadedFile::fake()->image('guest.jpg', 300, 300);

        $uploadResponse = $this->postJson(route('profile.image.update'), [
            'profile_image' => $file,
        ]);
        $uploadResponse->assertUnauthorized();

        $deleteResponse = $this->deleteJson(route('profile.image.delete'));
        $deleteResponse->assertUnauthorized();
    }

    public function test_mobile_profile_view_renders_successfully(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Janessa Herminado',
            'student_number' => '2024-00123',
        ]);

        $response = $this->actingAs($student)->get(route('mobile.profile'));
        $response->assertOk();
        $response->assertSee('Janessa Herminado');
        $response->assertSee('2024-00123');
        $response->assertSee('ppm-avatar-dropzone', false);
    }

    public function test_user_cannot_alter_another_users_profile_image(): void
    {
        Storage::fake('public');

        $userA = User::factory()->create([
            'role' => 'student',
            'profile_image' => 'profile_images/userA.jpg',
        ]);
        Storage::disk('public')->put('profile_images/userA.jpg', 'userA-content');

        $userB = User::factory()->create([
            'role' => 'student',
            'profile_image' => 'profile_images/userB.jpg',
        ]);
        Storage::disk('public')->put('profile_images/userB.jpg', 'userB-content');

        // User A performs an upload attempting to target User B by injecting user_id
        $newFile = UploadedFile::fake()->image('new.jpg', 300, 300);
        $response = $this->actingAs($userA)->postJson(route('profile.image.update'), [
            'profile_image' => $newFile,
            'user_id' => $userB->id,
            'id' => $userB->id,
        ]);

        $response->assertOk();

        $userA->refresh();
        $userB->refresh();

        // User B's image must remain untouched!
        $this->assertEquals('profile_images/userB.jpg', $userB->profile_image);
        Storage::disk('public')->assertExists('profile_images/userB.jpg');

        // User A's image was updated
        $this->assertNotEquals('profile_images/userA.jpg', $userA->profile_image);
        Storage::disk('public')->assertExists($userA->profile_image);
    }
}
