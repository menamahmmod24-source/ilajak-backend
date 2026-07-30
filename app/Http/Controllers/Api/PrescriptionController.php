<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PrescriptionController extends Controller
{
    /**
     * Get active prescriptions list with filtering & search.
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

        $patientId = $request->query('patient_id', $user->id);

        $query = Prescription::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patientId);

        // Filter by status (e.g., active, completed, expiring_soon)
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // Search by medication or doctor name
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%{$search}%")
                  ->orWhereHas('doctor.user', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $prescriptions = $query->orderBy('created_at', 'desc')->get();

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
            'remaining_refills' => 'nullable|integer|min:0',
            'expires_at'      => 'nullable|date',
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
            'appointment_id'    => $appointment->id,
            'patient_id'        => $appointment->patient_id,
            'doctor_id'         => $doctor->id,
            'details'           => $details,
            'file_path'         => $validated['file_path'] ?? null,
            'status'            => 'active',
            'remaining_refills' => $validated['remaining_refills'] ?? 0,
            'expires_at'        => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Prescription created successfully.',
            'data'    => $prescription
        ], 201);
    }

    /**
     * Get specific prescription details.
     */
    public function show($id)
    {
        $prescription = Prescription::with(['doctor.user', 'appointment'])->find($id);

        if (!$prescription) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Prescription not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $prescription
        ], 200);
    }

    /**
     * Get prescription history (filtered by year, category, status).
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $patientId = $request->query('patient_id', $user->id);

        $query = Prescription::with(['doctor.user'])
            ->where('patient_id', $patientId);

        if ($request->has('year')) {
            $query->whereYear('created_at', $request->query('year'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('details', 'like', "%{$search}%");
        }

        $history = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'data'   => $history
        ], 200);
    }

    /**
     * Request a refill for a prescription.
     */
    public function refill($id)
    {
        $prescription = Prescription::find($id);

        if (!$prescription) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Prescription not found.'
            ], 404);
        }

        if ($prescription->remaining_refills <= 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No remaining refills available for this prescription.'
            ], 400);
        }

        $prescription->decrement('remaining_refills');

        return response()->json([
            'status'  => 'success',
            'message' => 'Refill request submitted successfully.',
            'data'    => $prescription->fresh()
        ], 200);
    }

    /**
     * Reorder medication from prescription history.
     */
    public function reorder($id)
    {
        $prescription = Prescription::find($id);

        if (!$prescription) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Prescription not found.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Reorder initiated successfully.',
            'data'    => [
                'prescription_id' => $prescription->id,
                'details'         => $prescription->details
            ]
        ], 200);
    }

    /**
     * Download prescription file/PDF.
     */
    public function download($id)
    {
        $prescription = Prescription::find($id);

        if (!$prescription || !$prescription->file_path) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Prescription file not found.'
            ], 404);
        }

        if (!Storage::disk('public')->exists($prescription->file_path)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'File does not exist on server.'
            ], 404);
        }

        return response()->download(Storage::disk('public')->path($prescription->file_path));
    }
}
