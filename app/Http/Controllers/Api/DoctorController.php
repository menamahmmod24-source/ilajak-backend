<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\AppointmentResource;
use Carbon\Carbon;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors with optional filters (search, specialization).
     */
    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'clinics']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                })->orWhere('specialization', 'like', "%{$search}%");
            });
        }

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

    /**
     * Get doctor portal dashboard stats and current overview.
     */
    public function dashboard(Request $request)
    {
        $doctor = $request->user()->doctorProfile;

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Authenticated user is not registered as a doctor.'
            ], 403);
        }

        $today = Carbon::today()->toDateString();

        $todayAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('date', $today)
            ->count();

        $pendingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->count();

        $completedAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->count();

        $newPatientsCount = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('created_at', $today)
            ->distinct('patient_id')
            ->count('patient_id');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'doctor_name' => 'Dr. ' . $request->user()->name,
                'date'        => Carbon::now()->format('l, F j, Y'),
                'stats'       => [
                    'today_appointments'     => $todayAppointments,
                    'pending_appointments'   => $pendingAppointments,
                    'completed_appointments' => $completedAppointments,
                    'new_patients'           => $newPatientsCount,
                ]
            ]
        ], 200);
    }

    /**
     * List all patients seen by or scheduled with this doctor.
     */
    public function patients(Request $request)
    {
        $doctor = $request->user()->doctorProfile;

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Authenticated user is not registered as a doctor.'
            ], 403);
        }

        $search = $request->query('search');

        $query = User::where('role', 'patient')
            ->whereHas('patientAppointments', function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $patients = $query->get()->map(function ($patient) use ($doctor) {
            $lastVisit = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('status', 'completed')
                ->latest('date')
                ->first();

            $nextAppointment = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('date', '>=', Carbon::today()->toDateString())
                ->oldest('date')
                ->first();

            return [
                'id'               => $patient->id,
                'name'             => $patient->name,
                'age'              => $patient->dob ? Carbon::parse($patient->dob)->age : null,
                'gender'           => ucfirst($patient->gender ?? 'Unknown'),
                'blood_type'       => $patient->blood_type,
                'status'           => $patient->status ?? 'Active',
                'last_visit'       => $lastVisit ? Carbon::parse($lastVisit->date)->format('M d, Y') : 'N/A',
                'next_appointment' => $nextAppointment ? Carbon::parse($nextAppointment->date)->format('M d, Y') . ' at ' . $nextAppointment->slot_time : 'Not Scheduled',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $patients
        ], 200);
    }

    /**
     * List appointments assigned to the logged-in doctor.
     */
    public function appointments(Request $request)
    {
        $doctor = $request->user()->doctorProfile;

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Authenticated user is not registered as a doctor.'
            ], 403);
        }

        $query = Appointment::with(['patient', 'clinic'])
            ->where('doctor_id', $doctor->id);

        if ($request->has('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $appointments = $query->orderBy('date', 'asc')
            ->orderBy('slot_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => AppointmentResource::collection($appointments)
        ], 200);
    }
}
