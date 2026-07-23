<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register Patient
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|max:255|unique:users',
            'password'    => 'required|string|min:8|confirmed',
            'phone'       => 'nullable|string|unique:users',
            'national_id' => 'nullable|string|unique:users',
            'gender'      => 'nullable|in:male,female',
            'dob'         => 'nullable|date',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'phone'       => $validated['phone'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'role'        => 'patient',
            'gender'      => $validated['gender'] ?? null,
            'dob'         => $validated['dob'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token'   => $token,
            'user'    => $user
        ], 201);
    }

    // Login (Patient / Unified)
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Your account has been suspended.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user->load('doctorProfile', 'clinics')
        ]);
    }

    // Register Doctor
    public function registerDoctor(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users',
            'password'               => 'required|string|min:8|confirmed',
            'phone'                  => 'required|string|unique:users',
            'national_id'            => 'required|string|unique:users',
            'gender'                 => 'required|in:male,female',
            'medical_license_number' => 'required|string|unique:doctors',
            'specialization'         => 'required|string|max:255',
            'bio'                    => 'nullable|string',
            'clinic_id'              => 'nullable|exists:clinics,id',
        ]);

        // Wrap user and doctor creation in a transaction
        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'password'    => Hash::make($validated['password']),
                'phone'       => $validated['phone'],
                'national_id' => $validated['national_id'],
                'role'        => 'doctor',
                'gender'      => $validated['gender'],
                'status'      => 'active',
            ]);

            Doctor::create([
                'user_id'                => $user->id,
                'medical_license_number' => $validated['medical_license_number'],
                'specialization'         => $validated['specialization'],
                'bio'                    => $validated['bio'] ?? null,
            ]);

            if (!empty($validated['clinic_id'])) {
                $user->clinics()->attach($validated['clinic_id'], ['type' => 'doctor']);
            }

            return $user;
        });

        $token = $user->createToken('doctor_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Doctor account registered successfully',
            'token'   => $token,
            'user'    => $user->load('doctorProfile', 'clinics')
        ], 201);
    }

    // Doctor Login
    public function doctorLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || $user->role !== 'doctor') {
            throw ValidationException::withMessages([
                'email' => ['Invalid doctor credentials or account is not registered as a doctor.'],
            ]);
        }

        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Your doctor account has been suspended.'], 403);
        }

        $token = $user->createToken('doctor_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Doctor login successful',
            'token'   => $token,
            'user'    => $user->load('doctorProfile', 'clinics')
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    // Get Logged-in User Profile
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load(['doctorProfile', 'clinics'])
        ]);
    }
}
