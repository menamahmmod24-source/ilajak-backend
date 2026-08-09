<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
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
                'status'  => 'error',
                'message' => 'Unauthorized: Only doctor accounts can set schedules.'
            ], 403);
        }

        $validated = $request->validate([
            'clinic_id'             => 'required|exists:clinics,id',
            'specific_date'         => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'day_of_week'           => 'required_without:specific_date|nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time'           => 'required|date_format:H:i',
            'end_time'             => 'required|date_format:H:i|after:start_time',
            'slot_duration_minutes' => 'nullable|integer|min:10|max:120',
            'is_day_off'            => 'nullable|boolean',
        ]);

        // If a specific date is provided, derive day_of_week from it
        $dayOfWeek = !empty($validated['specific_date'])
            ? Carbon::parse($validated['specific_date'])->format('l')
            : $validated['day_of_week'];

        $schedule = DoctorSchedule::updateOrCreate(
            [
                'doctor_id'     => $doctor->id,
                'clinic_id'     => $validated['clinic_id'],
                'day_of_week'   => $dayOfWeek,
                'specific_date' => $validated['specific_date'] ?? null,
            ],
            [
                'start_time'            => $validated['start_time'],
                'end_time'              => $validated['end_time'],
                'slot_duration_minutes' => $validated['slot_duration_minutes'] ?? 30,
                'is_day_off'            => $validated['is_day_off'] ?? false,
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Schedule updated successfully.',
            'data'    => $schedule
        ], 200);
    }

    /**
     * Get calculated time slots for a specific doctor.
     */
    public function availableSlots(Request $request, $doctorId)
    {
        $request->validate([
            'clinic_id'     => 'nullable|exists:clinics,id',
            'day_of_week'   => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'specific_date' => 'nullable|date_format:Y-m-d',
        ]);

        $query = DoctorSchedule::where('doctor_id', $doctorId);

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->clinic_id);
        }

        // 1. Check for specific_date match first, fallback to recurring day_of_week
        if ($request->filled('specific_date')) {
            $dayOfWeek = Carbon::parse($request->specific_date)->format('l');

            $query->where(function ($q) use ($request, $dayOfWeek) {
                $q->where('specific_date', $request->specific_date)
                  ->orWhere(function ($subQ) use ($dayOfWeek) {
                      $subQ->whereNull('specific_date')
                           ->where('day_of_week', $dayOfWeek);
                  });
            });
        } elseif ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Order by specific_date DESC to prioritize explicit date overrides over weekly schedules
        $schedule = $query->orderByRaw('specific_date IS NULL ASC')->first();

        if (!$schedule || $schedule->is_day_off) {
            return response()->json([
                'status'          => 'success',
                'message'         => 'Doctor is not available or taking a day off on this schedule.',
                'specific_date'   => $request->input('specific_date', Carbon::now()->toDateString()),
                'available_slots' => []
            ], 200);
        }

        // Generate time slots based on schedule bounds
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $duration = $schedule->slot_duration_minutes ?? 30;

        $allSlots = [];

        while ($startTime->lt($endTime)) {
            $slotStart = $startTime->format('H:i');
            $startTime->addMinutes($duration);
            $slotEnd = $startTime->format('H:i');

            if ($startTime->lte($endTime)) {
                $allSlots[] = [
                    'start_time' => $slotStart,
                    'end_time'   => $slotEnd,
                ];
            }
        }

        return response()->json([
            'status'          => 'success',
            'specific_date'   => $request->input('specific_date', $schedule->specific_date ?? Carbon::now()->toDateString()),
            'day_of_week'     => $schedule->day_of_week,
            'clinic_id'       => $schedule->clinic_id,
            'available_slots' => $allSlots
        ], 200);
    }
}
