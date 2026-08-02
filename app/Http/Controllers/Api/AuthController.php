<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Helper to return standard auth response structure
     */
    private function respondWithToken($user, $message, $code = 200)
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => $message,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user
        ], $code);
    }

    /**
     * Patient Register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|string|email|max:255|unique:users',
            'password'          => 'required|string|min:8|confirmed',
            'phone'             => 'required|string|max:20',
            'national_id'       => 'nullable|string|max:50',
            'gender'            => 'nullable|string',
            'dob'               => 'nullable|date',
            'date_of_birth'     => 'nullable|date', // Accept date_of_birth if sent by frontend
            'address'           => 'nullable|string',
            'permanent_address' => 'nullable|string', // Accept permanent_address if sent by frontend
            'blood_type'        => 'nullable|string|max:10',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'phone'       => $validated['phone'],
            'national_id' => $validated['national_id'] ?? null,
            'gender'      => $validated['gender'] ?? null,
            'dob'         => $request->input('dob') ?? $request->input('date_of_birth'), // Strictly populates 'dob'
            'address'     => $request->input('address') ?? $request->input('permanent_address'),
            'blood_type'  => $validated['blood_type'] ?? null,
            'role'        => 'patient',
            'status'      => 'active',
        ]);

        return $this->respondWithToken($user, 'Patient account registered successfully', 201);
    }

    /**
     * Doctor Register
     */
    public function registerDoctor(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users',
            'password'               => 'required|string|min:8|confirmed',
            'phone'                  => 'nullable|string|max:20|unique:users,phone',
            'national_id'            => 'nullable|string|max:50|unique:users,national_id',
            'gender'                 => 'nullable|string',
            'medical_id'             => 'required_without_all:medical_license_number,npi_number|nullable|string|max:100',
            'medical_license_number' => 'required_without_all:medical_id,npi_number|nullable|string|max:100',
            'npi_number'             => 'required_without_all:medical_id,medical_license_number|nullable|string|max:100',
            'specialization'         => 'nullable|string|max:100',
            'bio'                    => 'nullable|string',
        ]);

        $licenseNumber = $request->input('medical_id')
            ?? $request->input('medical_license_number')
            ?? $request->input('npi_number');

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'phone'       => $validated['phone'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'gender'      => $validated['gender'] ?? null,
            'role'        => 'doctor',
            'status'      => 'active',
        ]);

        Doctor::create([
            'user_id'                => $user->id,
            'medical_license_number' => $licenseNumber,
            'specialization'         => $validated['specialization'] ?? 'General Practitioner',
            'bio'                    => $validated['bio'] ?? null,
            'consultation_fee'       => 0.00,
        ]);

        $user->load(['doctorProfile', 'clinics']);

        return $this->respondWithToken($user, 'Doctor account registered successfully', 201);
    }

    /**
     * Unified Login (Patient & Doctor)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->role === 'doctor') {
            $user->load(['doctorProfile', 'clinics']);
        }

        return $this->respondWithToken($user, 'Logged in successfully.');
    }

    /**
     * Forgot Password - Request Reset Link / OTP
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $otp = rand(100000, 999999);

        try {
            Mail::to($request->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Reset 6-digit verification code sent to your email address.'
        ], 200);
    }

    /**
     * Verify 6-digit OTP Code
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|numeric|digits:6',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP verified successfully. You can now reset your password.'
        ], 200);
    }

    /**
     * Resend OTP Code
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        return response()->json([
            'status'  => 'success',
            'message' => 'A new 6-digit OTP code has been sent to your email.'
        ], 200);
    }

    /**
     * Reset Password Endpoint
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|numeric|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Password reset successfully. You can now log in with your new password.'
        ], 200);
    }

    /**
     * Change Password (from Profile Settings)
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided current password does not match our records.'
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Password updated successfully.'
        ], 200);
    }

    /**
     * Logout Doctor / Patient
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully.'
        ], 200);
    }
}
