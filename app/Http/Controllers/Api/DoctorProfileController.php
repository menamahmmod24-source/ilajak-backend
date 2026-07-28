<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorProfileController extends Controller
{
    /**
     * Get Doctor Profile details (Screen 1)
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Only doctor accounts can access this profile.'
            ], 403);
        }

        $user->load(['doctorProfile', 'clinics']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'status'         => $user->status,
                'avatar_url'     => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'specialization' => $user->doctorProfile->specialization ?? null,
                'license_number' => $user->doctorProfile->medical_license_number ?? null,
                'bio'            => $user->doctorProfile->bio ?? null,
                'clinics'        => $user->clinics ? $user->clinics->map(function ($clinic) {
                    return [
                        'id'   => $clinic->id,
                        'name' => $clinic->name,
                    ];
                }) : [],
            ]
        ], 200);
    }

    /**
     * Update Doctor Profile details
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized.'
            ], 403);
        }

        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'phone'          => 'sometimes|nullable|string|max:20',
            'specialization' => 'sometimes|string|max:100',
            'bio'            => 'sometimes|nullable|string',
        ]);

        // Update User info if provided
        if (isset($validated['name']) || isset($validated['phone'])) {
            $user->update(array_filter([
                'name'  => $validated['name'] ?? $user->name,
                'phone' => $validated['phone'] ?? $user->phone,
            ]));
        }

        // Update Doctor Profile info
        if ($user->doctorProfile) {
            $user->doctorProfile->update(array_filter([
                'specialization' => $validated['specialization'] ?? $user->doctorProfile->specialization,
                'bio'            => $validated['bio'] ?? $user->doctorProfile->bio,
            ]));
        }

        $user->load(['doctorProfile', 'clinics']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Doctor profile updated successfully.',
            'data'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'avatar_url'     => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'specialization' => $user->doctorProfile->specialization ?? null,
                'license_number' => $user->doctorProfile->medical_license_number ?? null,
                'bio'            => $user->doctorProfile->bio ?? null,
            ]
        ], 200);
    }

    /**
     * Update Profile Avatar Photo
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
            $user->save();
        }

        return response()->json([
            'status'     => 'success',
            'message'    => 'Profile picture updated successfully.',
            'avatar_url' => asset('storage/' . $user->avatar),
        ], 200);
    }
}
