<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'guest_name',
        'guest_email',
        'role',
        'rsvp_status',
        'attendance_status',
        'checked_in_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user ? $this->user->name : ($this->guest_name ?? 'Guest');
    }

    public function getDisplayEmailAttribute(): ?string
    {
        return $this->user ? $this->user->email : $this->guest_email;
    }

    public function getRsvpBadgeClassAttribute(): string
    {
        return match ($this->rsvp_status) {
            'accepted' => 'bg-success',
            'declined' => 'bg-danger',
            'tentative' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }

    public function getAttendanceBadgeClassAttribute(): string
    {
        return match ($this->attendance_status) {
            'present' => 'bg-success',
            'absent' => 'bg-danger',
            'late' => 'bg-warning text-dark',
            'excused' => 'bg-info text-dark',
            default => 'bg-light text-muted border',
        };
    }
}
