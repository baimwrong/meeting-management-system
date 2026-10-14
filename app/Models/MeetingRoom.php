<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'floor_location',
        'capacity',
        'equipment',
        'hourly_rate',
        'image_url',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'equipment' => 'array',
            'capacity' => 'integer',
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'room_id');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'available' => 'bg-success',
            'maintenance' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }

    /**
     * Check if room is available during the given window
     */
    public function isAvailable(string $date, string $startTime, string $endTime, ?int $ignoreMeetingId = null): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        $query = $this->meetings()
            ->where('meeting_date', $date)
            ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            });

        if ($ignoreMeetingId) {
            $query->where('id', '!=', $ignoreMeetingId);
        }

        return !$query->exists();
    }
}
