<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Appointment;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    /**
     * Get prescriptions (Patients view their own, Doctors view by patient_id query param).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Determine target patient ID:
        // Uses ?patient_id=1 if passed in query params; otherwise defaults to logged-in user ID.
        $patientId = $request->query('patient_id', $user->id);

        $prescriptions = Prescription::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $prescriptions
        ], 200);
    }

    /**
     * Store a new prescription using existing database columns.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $doctor = $user->doctorProfile ?? null;

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Only doctor accounts can issue prescriptions.'
            ], 403);
        }

        $validated = $request->validate([
            'appointment_id'  => 'required|exists:appointments,id',
            'details'         => 'nullable|string',
            'medication_name' => 'nullable|string',
            'dosage'          => 'nullable|string',
            'instructions'    => 'nullable|string',
            'file_path'       => 'nullable|string',
        ]);

        $appointment = Appointment::find($validated['appointment_id']);

        if (!$appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment not found.'
            ], 404);
        }

        // Auto-format into 'details' if individual fields are passed
        $details = $validated['details'] ?? null;

        if (!$details && isset($validated['medication_name'])) {
            $details = "Medication: {$validated['medication_name']}\n"
                     . "Dosage: " . ($validated['dosage'] ?? 'N/A') . "\n"
                     . "Instructions: " . ($validated['instructions'] ?? 'N/A');
        }

        $prescription = Prescription::create([
            'appointment_id' => $appointment->id,
            'patient_id'     => $appointment->patient_id,
            'doctor_id'      => $doctor->id,
            'details'        => $details,
            'file_path'      => $validated['file_path'] ?? null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Prescription created successfully.',
            'data'    => $prescription
        ], 201);
    }
}
