<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    /**
     * Get medical records for the authenticated patient or query by patient_id.
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
        // If doctor/admin passes ?patient_id=5 in URL params, use that; otherwise default to logged-in user ID.
        $patientId = $request->query('patient_id', $user->id);

        $records = MedicalRecord::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $records
        ], 200);
    }

    /**
     * Store or update medical record fields for a patient.
     */
    public function store(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
    }

    $validated = $request->validate([
        'patient_id'        => 'required|exists:users,id',
        'chronic_diseases'  => 'nullable|string',
        'allergies'         => 'nullable|string',
        'lab_results'       => 'nullable|array',
        'radiology_results' => 'nullable|array',
        'attachments'       => 'nullable', // <-- Added to validation
    ]);

    // Handle uploaded file OR array passed in JSON
    $attachmentData = null;

    if ($request->hasFile('attachment')) {
        // Form-data file upload
        $path = $request->file('attachment')->store('medical_records', 'public');
        $attachmentData = [$path];
    } elseif ($request->filled('attachments')) {
        // Raw JSON body array/string
        $attachmentData = $request->input('attachments');
    }

    $record = MedicalRecord::create([
        'patient_id'        => $validated['patient_id'],
        'chronic_diseases'  => $validated['chronic_diseases'] ?? null,
        'allergies'         => $validated['allergies'] ?? null,
        'lab_results'       => $validated['lab_results'] ?? null,
        'radiology_results' => $validated['radiology_results'] ?? null,
        'attachments'       => $attachmentData,
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Medical record created successfully.',
        'data'    => $record
    ], 201);
}
}
