<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingRoom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class MeetingRoomService
{
    /**
     * Check if a room is available for the given date and time slot
     * Returns an array with ['available' => bool, 'conflict' => ?Meeting]
     */
    public function checkRoomAvailability(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeMeetingId = null): array
    {
        $room = MeetingRoom::findOrFail($roomId);

        if ($room->status !== 'available') {
            return [
                'available' => false,
                'message' => "Room {$room->name} is currently marked as '{$room->status}' and cannot be booked.",
                'conflict' => null,
            ];
        }

        $query = Meeting::where('room_id', $roomId)
            ->whereDate('meeting_date', $date)
            ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            });

        if ($excludeMeetingId) {
            $query->where('id', '!=', $excludeMeetingId);
        }

        $conflict = $query->with('organizer')->first();

        if ($conflict) {
            $timeSlot = Carbon::parse($conflict->start_time)->format('h:i A') . ' - ' . Carbon::parse($conflict->end_time)->format('h:i A');
            $organizerName = $conflict->organizer ? $conflict->organizer->name : 'Unknown';
            return [
                'available' => false,
                'message' => "Room {$room->name} is already booked from {$timeSlot} by {$organizerName} for '{$conflict->title}'.",
                'conflict' => $conflict,
            ];
        }

        return [
            'available' => true,
            'message' => "Room {$room->name} is available for booking.",
            'conflict' => null,
        ];
    }

    /**
     * Get all rooms with their availability status for a specific date and time slot
     */
    public function getAvailableRoomsForSlot(string $date, string $startTime, string $endTime, ?int $minCapacity = null): Collection
    {
        $query = MeetingRoom::where('status', 'available');

        if ($minCapacity) {
            $query->where('capacity', '>=', $minCapacity);
        }

        $rooms = $query->get();

        return $rooms->filter(function ($room) use ($date, $startTime, $endTime) {
            return $room->isAvailable($date, $startTime, $endTime);
        });
    }

    /**
     * Get room utilization statistics for dashboard
     */
    public function getUtilizationStats(): array
    {
        $totalRooms = MeetingRoom::count();
        $availableRooms = MeetingRoom::where('status', 'available')->count();
        $maintenanceRooms = MeetingRoom::where('status', 'maintenance')->count();

        // Calculate booked hours today
        $today = Carbon::today()->toDateString();
        $todayMeetings = Meeting::whereDate('meeting_date', $today)
            ->whereNotNull('room_id')
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->get();

        $totalBookedHours = 0;
        foreach ($todayMeetings as $meeting) {
            $start = Carbon::parse($meeting->start_time);
            $end = Carbon::parse($meeting->end_time);
            $totalBookedHours += $end->diffInMinutes($start) / 60;
        }

        // Standard 9-hour workday (8 AM to 5 PM) per active room
        $capacityHours = max(1, $availableRooms * 9);
        $utilizationRate = min(100, round(($totalBookedHours / $capacityHours) * 100, 1));

        return [
            'total_rooms' => $totalRooms,
            'available_rooms' => $availableRooms,
            'maintenance_rooms' => $maintenanceRooms,
            'booked_hours_today' => round($totalBookedHours, 1),
            'utilization_rate' => $utilizationRate,
        ];
    }
}
