<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DoctorScheduleController extends Controller
{
    /**
     * Set/Update working schedule for the authenticated doctor.
     */
    public function store(Request $request)
    {
        $doctor = $request->user()->doctorProfile;

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only doctor accounts can set schedules.'
            ], 403);
        }

        $validated = $request->validate([
            'clinic_id' => 'required|exists:clinics,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration_minutes' => 'nullable|integer|min:10|max:120',
        ]);

        $schedule = DoctorSchedule::updateOrCreate(
            [
                'doctor_id' => $doctor->id,
                'clinic_id' => $validated['clinic_id'],
                'day_of_week' => $validated['day_of_week'],
            ],
            [
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'slot_duration_minutes' => $validated['slot_duration_minutes'] ?? 30,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule updated successfully.',
            'data' => $schedule
        ], 200);
    }

    /**
     * Get calculated available time slots for a specific doctor, clinic, and date.
     */
    public function availableSlots(Request $request, $doctorId)
    {
        $request->validate([
            'clinic_id' => 'nullable|exists:clinics,id', // <-- Changed from required to nullable
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->format('l'); // e.g. "Monday"

        // Build query for schedule
        $query = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek);

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->clinic_id);
        }

        $schedule = $query->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'success',
                'message' => 'Doctor does not work on this day.',
                'slots' => []
            ], 200);
        }

        // Generate full slot times
        $startTime = Carbon::parse($request->date . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($request->date . ' ' . $schedule->end_time);
        $duration = $schedule->slot_duration_minutes ?? 30;

        $allSlots = [];
        while ($startTime->lt($endTime)) {
            $slotStart = $startTime->format('H:i:s');
            $startTime->addMinutes($duration);
            $slotEnd = $startTime->format('H:i:s');

            if ($startTime->lte($endTime)) {
                $allSlots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                ];
            }
        }

        // Fetch already booked slots
        $bookedQuery = Appointment::where('doctor_id', $doctorId)
            ->where('date', $request->date)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($request->filled('clinic_id')) {
            $bookedQuery->where('clinic_id', $request->clinic_id);
        }

        $bookedSlots = $bookedQuery->pluck('slot_time')->toArray();

        // Filter out booked slots
        $availableSlots = array_values(array_filter($allSlots, function ($slot) use ($bookedSlots) {
            return !in_array($slot['start_time'], $bookedSlots);
        }));

        return response()->json([
            'status' => 'success',
            'date' => $request->date,
            'day' => $dayOfWeek,
            'clinic_id' => $schedule->clinic_id,
            'available_slots' => $availableSlots
        ], 200);
    }
}