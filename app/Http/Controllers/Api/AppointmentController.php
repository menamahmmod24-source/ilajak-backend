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
     * Fetch available time slots for a doctor on a specific date based on schedule.
     */
    public function getAvailableSlots(Request $request, $doctorId)
    {
        $validated = $request->validate([
            'date'      => 'required|date_format:Y-m-d',
            'clinic_id' => 'nullable|exists:clinics,id', // <-- Ensure this says 'nullable', NOT 'required'
        ]);

        $dayOfWeek = Carbon::parse($validated['date'])->format('l');

        $query = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek);

        // If clinic_id is provided, filter by it; otherwise get default schedule for that day
        if (!empty($validated['clinic_id'])) {
            $query->where('clinic_id', $validated['clinic_id']);
        }

        $schedule = $query->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'success',
                'date'   => $validated['date'],
                'data'   => []
            ], 200);
        }

        $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->where('date', $validated['date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('slot_time')
            ->toArray();

        $slots = [];
        $startTime = Carbon::parse($schedule->start_time ?? '09:00:00');
        $endTime   = Carbon::parse($schedule->end_time ?? '17:00:00');

        while ($startTime->lt($endTime)) {
            $formattedSlot = $startTime->format('H:i');
            if (!in_array($formattedSlot, $bookedSlots)) {
                $slots[] = $formattedSlot;
            }
            $startTime->addMinutes(30);
        }

        return response()->json([
            'status' => 'success',
            'date'   => $validated['date'],
            'data'   => $slots
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

        // 1. Verify doctor works on this day/clinic
        $schedule = DoctorSchedule::where('doctor_id', $validated['doctor_id'])
            ->where('clinic_id', $validated['clinic_id'])
            ->where('day_of_week', $dayOfWeek)
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

        // 3. Create appointment (defaults to confirmed upon successful booking)
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

        // Tab Filter Options: ?type=upcoming | ?type=completed | ?type=cancelled
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
                // Allows patient or assigned doctor to modify status
                $q->where('patient_id', $request->user()->id)
                    ->orWhere('doctor_id', $request->user()->doctor?->id);
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
