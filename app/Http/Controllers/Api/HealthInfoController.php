<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HealthInfoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load(['allergies', 'chronicConditions', 'medications']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'patient_id'          => '#' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'patient_name'        => $user->name,
                'blood_type'          => $user->blood_type ?? 'O+',
                'status'              => 'Verified',
                'allergies'           => $user->allergies->map(fn($a) => ['id' => $a->id, 'name' => $a->name]),
                'chronic_conditions'  => $user->chronicConditions->map(fn($c) => [
                    'id'           => $c->id,
                    'name'         => $c->name,
                    'diagnosed_at' => $c->diagnosed_at,
                ]),
                'current_medications' => $user->medications->map(fn($m) => [
                    'id'        => $m->id,
                    'name'      => $m->name,
                    'dosage'    => $m->dosage,
                    'frequency' => $m->frequency,
                ]),
                'emergency_contact'   => [
                    'name'     => $user->emergency_contact_name ?? 'Sarah Khaled',
                    'relation' => $user->emergency_contact_relation ?? 'Wife',
                    'phone'    => $user->emergency_contact_phone ?? '+971500000000',
                ]
            ]
        ], 200);
    }

    public function storeMedication(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'dosage'    => 'required|string|max:100',
            'frequency' => 'required|string|max:100',
        ]);

        $medication = $request->user()->medications()->create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Medication added successfully.',
            'data'    => $medication
        ], 201);
    }

    public function destroyMedication(Request $request, $id)
    {
        $deleted = $request->user()->medications()->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Medication not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Medication deleted successfully.'
        ], 200);
    }

    /**
     * Add a new Allergy tag
     */
    public function storeAllergy(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $allergy = $request->user()->allergies()->create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Allergy added successfully.',
            'data'    => $allergy,
        ], 201);
    }

    /**
     * Remove an Allergy tag
     */
    public function destroyAllergy(Request $request, $id)
    {
        $deleted = $request->user()->allergies()->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Allergy not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Allergy removed successfully.'
        ], 200);
    }

    /**
     * Add a Chronic Condition
     */
    public function storeChronicCondition(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'diagnosed_at' => 'required|string|max:100', // e.g., "April 2022"
        ]);

        $condition = $request->user()->chronicConditions()->create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Chronic condition added successfully.',
            'data'    => $condition,
        ], 201);
    }

    /**
     * Remove a Chronic Condition
     */
    public function destroyChronicCondition(Request $request, $id)
    {
        $deleted = $request->user()->chronicConditions()->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Chronic condition not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Chronic condition removed successfully.'
        ], 200);
    }

    /**
     * Update Emergency Contact Details (Bottom blue card in UI)
     */
    public function updateEmergencyContact(Request $request)
    {
        $validated = $request->validate([
            'emergency_contact_name'     => 'required|string|max:255',
            'emergency_contact_relation' => 'required|string|max:100',
            'emergency_contact_phone'    => 'required|string|max:20',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Emergency contact updated successfully.',
            'data'    => [
                'name'     => $user->emergency_contact_name,
                'relation' => $user->emergency_contact_relation,
                'phone'    => $user->emergency_contact_phone,
            ]
        ], 200);
    }
}
