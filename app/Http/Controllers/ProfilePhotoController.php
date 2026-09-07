<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoController extends Controller
{
    /**
     * Upload and update the authenticated user's profile picture.
     */
    public function update(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp,gif,heic,heif|max:5120|dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000',
        ], [
            'profile_image.required' => 'Please select an image file to upload.',
            'profile_image.image' => 'The selected file must be a valid image.',
            'profile_image.mimes' => 'Unsupported image format. Allowed formats: JPG, PNG, WEBP, HEIC.',
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

        try {
            // Secure internal filename generation
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'])) {
                $extension = 'jpg';
            }
            $safeFilename = Str::uuid()->toString() . '.' . $extension;

            // Handle cloud storage if Cloudinary is explicitly configured, otherwise store to public disk
            $storedPath = null;
            if (config('filesystems.default') === 'cloudinary' && config('filesystems.disks.cloudinary.url')) {
                $storedPath = $file->storeOnCloudinary('profile_images')->getSecurePath();
            } else {
                $storedPath = $file->storeAs('profile_images', $safeFilename, 'public');
            }

            // Remove previous local photo if one existed
            if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Save new path to user model
            $user->profile_image = $storedPath;
            $user->save();

            // Safe debug log without sensitive information
            Log::info('Profile photo updated', [
                'user_id' => $user->id,
                'role' => $user->role,
                'disk' => config('filesystems.default') === 'cloudinary' ? 'cloudinary' : 'public',
            ]);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture updated successfully!',
                    'image_url' => $user->profile_photo_url,
                    'versioned_url' => $user->profile_photo_url_with_version,
                    'has_custom' => true,
                ]);
            }

            return back()->with('success', 'Profile picture updated successfully!');
        } catch (\Throwable $e) {
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
                if (!str_starts_with($user->profile_image, 'http')) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                $user->profile_image = null;
                $user->save();

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
