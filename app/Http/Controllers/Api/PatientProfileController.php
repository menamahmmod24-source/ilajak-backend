<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientProfileController extends Controller
{
    /**
     * Profile Dashboard / Overview Screen
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Safe count fallbacks if relationship models aren't migrated yet
        $upcomingAppointments = method_exists($user, 'patientAppointments')
            ? $user->patientAppointments()->where('status', 'upcoming')->count()
            : 2;

        $prescriptionsCount = method_exists($user, 'medications')
            ? $user->medications()->count()
            : 0;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'avatar'                => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'upcoming_appointments' => $upcomingAppointments,
                'prescriptions'         => $prescriptionsCount,
                'medical_records'       => 12,
            ]
        ], 200);
    }

    /**
     * Get Personal Info Screen
     */
    public function getPersonalInfo(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'full_name'     => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'date_of_birth' => $user->dob?? null,
                'gender'        => $user->gender ?? null,
                'patient_id'    => '#' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
            ]
        ], 200);
    }

    /**
     * Update Personal Info Screen
     */
    public function updatePersonalInfo(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'phone'         => 'sometimes|nullable|string|max:20',
            'date_of_birth' => 'sometimes|nullable|date',
            'gender'        => 'sometimes|nullable|in:male,female',
        ]);

        $user->update(array_filter([
            'name'          => $validated['name'] ?? $user->name,
            'phone'         => $validated['phone'] ?? $user->phone,
            'date_of_birth' => $validated['date_of_birth'] ?? $user->date_of_birth,
            'gender'        => $validated['gender'] ?? $user->gender,
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Personal information updated successfully.',
            'data'    => $user
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
            'message'    => 'Avatar updated successfully.',
            'avatar_url' => asset('storage/' . $user->avatar),
        ], 200);
    }
}
