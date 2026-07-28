<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    /**
     * Display a listing of clinics with optional search and location filtering.
     */
    public function index(Request $request)
    {
        // Fix: Changed 'doctors.user' to 'doctors'
        $query = Clinic::with(['doctors']);

        // Filter by clinic name
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Filter by city / address
        if ($request->filled('city')) {
            $query->where('address', 'like', "%{$request->city}%");
        }

        $clinics = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $clinics
        ], 200);
    }

    /**
     * Display detailed profile for a specific clinic, including doctors.
     */
    public function show($id)
    {
        // Fix: Changed 'doctors.user' to 'doctors'
        $clinic = Clinic::with(['doctors'])->find($id);

        if (!$clinic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Clinic not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $clinic
        ], 200);
    }
}
