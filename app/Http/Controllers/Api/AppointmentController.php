<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Mark an appointment as completed and record optional visit notes.
     */
    public function completeAppointment(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $doctor = $request->user()->doctorProfile;

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Authenticated user is not registered as a doctor.'
            ], 403);
        }

        $appointment = Appointment::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment not found or not assigned to this doctor.'
            ], 404);
        }

        $appointment->update([
            'status' => 'completed',
            'notes'  => $validated['notes'] ?? $appointment->notes,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Appointment marked as completed.',
            'data'    => new AppointmentResource($appointment->load(['patient', 'doctor.user', 'clinic']))
        ], 200);
    }

    /**
     * Book an appointment for the authenticated patient.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'clinic_id' => 'required|exists:clinics,id',
            'date'      => 'required|date_format:Y-m-d|after_or_equal:today',
            'slot_time' => 'required',
            'notes'     => 'nullable|string|max:500',
        ]);

        $dayOfWeek = Carbon::parse($validated['date'])->format('l');

        // 1. Verify doctor works on this date or general day of week
        $dayOfWeek = Carbon::parse($validated['date'])->format('l');

        $schedule = DoctorSchedule::where('doctor_id', $validated['doctor_id'])
            ->where('clinic_id', $validated['clinic_id'])
            ->where(function ($query) use ($validated, $dayOfWeek) {
                $query->where('specific_date', $validated['date'])
                    ->orWhere(function ($subQuery) use ($dayOfWeek) {
                        $subQuery->whereNull('specific_date')
                            ->where('day_of_week', $dayOfWeek);
                    });
            })
            ->where('is_day_off', false)
            ->orderByRaw('specific_date IS NULL ASC')
            ->first();

        if (!$schedule) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The doctor is not available on this day.'
            ], 422);
        }

        // 2. Prevent duplicate bookings for the exact same slot
        $existingAppointment = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('date', $validated['date'])
            ->where('slot_time', $validated['slot_time'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($existingAppointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This time slot is already booked.'
            ], 422);
        }

        // 3. Create appointment
        $appointment = Appointment::create([
            'patient_id' => $request->user()->id,
            'doctor_id'  => $validated['doctor_id'],
            'clinic_id'  => $validated['clinic_id'],
            'date'       => $validated['date'],
            'slot_time'  => $validated['slot_time'],
            'notes'      => $validated['notes'] ?? null,
            'status'     => 'confirmed',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Appointment booked successfully.',
            'data'    => new AppointmentResource($appointment->load(['doctor.user', 'clinic']))
        ], 201);
    }

    /**
     * Display a listing of appointments for the authenticated user with status filtering.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Appointment::with(['doctor.user', 'clinic'])
            ->where('patient_id', $user->id);

        if ($request->has('type')) {
            $type = strtolower($request->type);
            if ($type === 'upcoming') {
                $query->whereIn('status', ['pending', 'confirmed'])
                    ->where('date', '>=', Carbon::today()->toDateString());
            } elseif ($type === 'completed') {
                $query->where('status', 'completed');
            } elseif ($type === 'cancelled' || $type === 'canceled') {
                $query->where('status', 'canceled');
            }
        }

        $appointments = $query->orderBy('date', 'desc')
            ->orderBy('slot_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => AppointmentResource::collection($appointments)
        ], 200);
    }

    /**
     * Update status or cancel an appointment.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,canceled',
        ]);

        $appointment = Appointment::where('id', $id)
            ->where(function ($q) use ($request) {
                $q->where('patient_id', $request->user()->id)
                    ->orWhere('doctor_id', $request->user()->doctorProfile?->id);
            })
            ->first();

        if (!$appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment not found or unauthorized.'
            ], 404);
        }

        $appointment->update([
            'status' => $validated['status']
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Appointment status updated successfully.',
            'data'    => new AppointmentResource($appointment->load(['doctor.user', 'clinic']))
        ], 200);
    }
}
