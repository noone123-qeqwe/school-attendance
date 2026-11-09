<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\FeatureFlagService;
use App\Services\ProfileFaceEnrollmentService;
use App\Services\ProfileFacePhotoService;

class ProfilePhotoController extends Controller
{
    /** Enroll a student's face from a camera capture, kept only in encrypted storage. */
    public function enrollFace(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'student') {
            return response()->json(['success' => false, 'message' => 'Face enrollment is available to student accounts only.'], 403);
        }

        if (!app(FeatureFlagService::class)->enabled('feature.face_verification')) {
            return response()->json(['success' => false, 'code' => 'FACE_VERIFICATION_DISABLED', 'message' => 'Face attendance enrollment is currently unavailable.'], 503);
        }

        $request->validate([
            'face_image' => 'required|image|mimes:jpeg,jpg|max:5120|dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ]);

        $file = $request->file('face_image');
        if (!$file || !$file->isValid()) {
            return response()->json(['success' => false, 'message' => 'The camera image could not be read. Please capture it again.'], 422);
        }

        $photos = app(ProfileFacePhotoService::class);
        $jpeg = $photos->normalizeForMatching($file->get());
        if ($jpeg === null) {
            return response()->json(['success' => false, 'code' => 'PROFILE_IMAGE_UNREADABLE', 'message' => 'The camera image could not be processed. Please capture it again.'], 422);
        }

        $quality = app(ProfileFaceEnrollmentService::class)->validate($jpeg);
        if (!($quality['valid'] ?? false)) {
            $status = ($quality['code'] ?? '') === 'MATCHER_UNAVAILABLE' ? 503 : 422;
            return response()->json([
                'success' => false,
                'code' => $quality['code'] ?? 'PROFILE_IMAGE_QUALITY',
                'message' => $quality['message'] ?? 'The captured face image did not pass quality checks.',
            ], $status);
        }

        try {
            DB::transaction(fn () => $photos->saveCameraEnrollment($user, $jpeg));
        } catch (\Throwable $e) {
            Log::error('Camera face enrollment storage failed', ['user_id' => $user->id, 'exception' => $e::class]);
            return response()->json(['success' => false, 'message' => 'The face enrollment could not be saved securely. Please try again.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Face registered securely for attendance verification.']);
    }

    /**
     * Upload and update the authenticated user's profile picture.
     */
    public function update(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120|dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000',
        ], [
            'profile_image.required' => 'Please select an image file to upload.',
            'profile_image.image' => 'The selected file must be a valid image.',
            'profile_image.mimes' => 'Unsupported image format. Use JPG, PNG, WEBP, or GIF for face matching.',
            'profile_image.max' => 'Image is too large. Maximum size is 5 MB.',
            'profile_image.dimensions' => 'Image dimensions must be between 100×100 and 5000×5000 pixels.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please sign in again.',
            ], 401);
        }

        $file = $request->file('profile_image');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file upload. Please choose a valid image.',
            ], 422);
        }

        $profileFacePhotos = app(ProfileFacePhotoService::class);
        $normalizedFacePhoto = $profileFacePhotos->normalizeForMatching($file->get());
        if ($normalizedFacePhoto === null) {
            return $this->faceEnrollmentFailure($request, [
                'code' => 'PROFILE_IMAGE_UNREADABLE',
                'message' => 'Choose a readable JPG, PNG, WEBP, or GIF photo with dimensions between 100 and 5000 pixels.',
            ], 422);
        }

        $faceMatchingEnabled = $user->role === 'student'
            && app(FeatureFlagService::class)->enabled('feature.face_verification');
        if ($faceMatchingEnabled) {
            $quality = app(ProfileFaceEnrollmentService::class)->validate($normalizedFacePhoto);
            if (!($quality['valid'] ?? false)) {
                $status = ($quality['code'] ?? '') === 'MATCHER_UNAVAILABLE' ? 503 : 422;
                return $this->faceEnrollmentFailure($request, $quality, $status);
            }
        }

        $successMessage = $faceMatchingEnabled
            ? 'Profile photo saved and checked for attendance face matching.'
            : 'Profile picture updated successfully!';

        $storedPath = null;
        try {
            // Secure internal filename generation
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $extension = 'jpg';
            }
            $safeFilename = Str::uuid()->toString() . '.' . $extension;

            // Handle cloud storage if Cloudinary is explicitly configured, otherwise store to public disk
            if (config('filesystems.default') === 'cloudinary' && config('filesystems.disks.cloudinary.url')) {
                $storedPath = $file->storeOnCloudinary('profile_images')->getSecurePath();
            } else {
                $storedPath = $file->storeAs('profile_images', $safeFilename, 'public');
            }
            if (!is_string($storedPath) || $storedPath === '') {
                throw new \RuntimeException('Profile photo storage failed.');
            }

            $previousPath = $user->profile_image;
            DB::transaction(function () use ($user, $normalizedFacePhoto, $storedPath, $profileFacePhotos) {
                $profileFacePhotos->saveNormalized($user, $normalizedFacePhoto, $storedPath);
                $user->profile_image = $storedPath;
                $user->save();
            });

            if ($previousPath && !str_starts_with($previousPath, 'http')) {
                Storage::disk('public')->delete($previousPath);
            }

            // Safe debug log without sensitive information
            Log::info('Profile photo updated', [
                'user_id' => $user->id,
                'role' => $user->role,
                'disk' => config('filesystems.default') === 'cloudinary' ? 'cloudinary' : 'public',
            ]);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'image_url' => $user->profile_photo_url,
                    'versioned_url' => $user->profile_photo_url_with_version,
                    'has_custom' => true,
                    'face_match_ready' => $faceMatchingEnabled,
                ]);
            }

            return back()->with('success', $successMessage);
        } catch (\Throwable $e) {
            if ($storedPath && !str_starts_with($storedPath, 'http') && $user->profile_image !== $storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            Log::error('Profile photo upload error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
            ]);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to update your profile picture. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Unable to update your profile picture. Please try again.');
        }
    }

    private function faceEnrollmentFailure(Request $request, array $result, int $status): mixed
    {
        $message = (string) ($result['message'] ?? 'This photo could not be used for face matching.');
        $code = (string) ($result['code'] ?? 'PROFILE_IMAGE_QUALITY');

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'code' => $code,
                'message' => $message,
                'errors' => ['profile_image' => [$message]],
            ], $status);
        }

        if ($status === 422) {
            return back()->withErrors(['profile_image' => $message]);
        }

        return back()->with('error', $message);
    }

    /**
     * Delete and remove the authenticated user's profile picture, restoring the default avatar.
     */
    public function destroy(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            if ($user->profile_image) {
                $previousPath = $user->profile_image;
                DB::transaction(function () use ($user) {
                    app(\App\Services\ProfileFacePhotoService::class)->delete($user);
                    $user->profile_image = null;
                    $user->save();
                });

                if (!str_starts_with($previousPath, 'http')) {
                    Storage::disk('public')->delete($previousPath);
                }

                Log::info('Profile photo removed', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                ]);
            }

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture removed successfully.',
                    'image_url' => $user->profile_photo_url,
                    'versioned_url' => $user->profile_photo_url,
                    'has_custom' => false,
                ]);
            }

            return back()->with('success', 'Profile picture removed successfully.');
        } catch (\Throwable $e) {
            Log::error('Profile photo delete error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
            ]);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to remove profile picture. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Unable to remove profile picture. Please try again.');
        }
    }
}
