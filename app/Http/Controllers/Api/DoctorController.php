<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Http\Resources\DoctorResource;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors with optional filters (search, specialization).
     */
    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'clinics']);

        // Filter by doctor name or specialization query
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                })->orWhere('specialization', 'like', "%{$search}%");
            });
        }

        // Filter by exact specialization
        if ($request->has('specialization') && !empty($request->specialization)) {
            $query->where('specialization', $request->specialization);
        }

        $doctors = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data'   => $doctors
        ], 200);
    }

    /**
     * Display recommended doctors / top specialists for home screen.
     */
    public function recommended()
{
    $doctors = Doctor::with(['user', 'clinics'])
        ->take(5)
        ->get();

    return response()->json([
        'status' => 'success',
        'data'   => DoctorResource::collection($doctors)
    ], 200);
}

    /**
     * Display detailed profile for a specific doctor.
     */
    public function show($id)
    {
        $doctor = Doctor::with(['user', 'clinics', 'schedules'])->find($id);

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Doctor not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $doctor
        ], 200);
    }
}