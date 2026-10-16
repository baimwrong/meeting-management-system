<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_code',
        'title',
        'description',
        'room_id',
        'organizer_id',
        'department_id',
        'meeting_type',
        'virtual_link',
        'meeting_date',
        'start_time',
        'end_time',
        'status',
        'cancellation_reason',
        'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'is_confidential' => 'boolean',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class, 'room_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(MeetingAgenda::class)->orderBy('order_index');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function minutes(): HasOne
    {
        return $this->hasOne(MeetingMinute::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'bg-primary',
            'in_progress' => 'bg-info text-dark',
            'completed' => 'bg-success',
            'rescheduled' => 'bg-warning text-dark',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getMeetingTypeBadgeClassAttribute(): string
    {
        return match ($this->meeting_type) {
            'in_person' => 'bg-success-subtle text-success border border-success-subtle',
            'virtual' => 'bg-info-subtle text-info border border-info-subtle',
            'hybrid' => 'bg-primary-subtle text-primary border border-primary-subtle',
            default => 'bg-light text-dark',
        };
    }

    public function getFormattedTimeRangeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('h:i A');
        $end = Carbon::parse($this->end_time)->format('h:i A');

        return "{$start} - {$end}";
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('meeting_date', Carbon::today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereDate('meeting_date', '>', Carbon::today())
                ->orWhere(function ($sub) {
                    $sub->whereDate('meeting_date', Carbon::today())
                        ->where('end_time', '>=', Carbon::now()->format('H:i:s'));
                });
        })->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where(function ($subDate) {
                $subDate->whereDate('meeting_date', '<', Carbon::today())
                    ->orWhere(function ($subTime) {
                        $subTime->whereDate('meeting_date', Carbon::today())
                            ->where('end_time', '<', Carbon::now()->format('H:i:s'));
                    });
            })->orWhere('status', 'completed');
        });
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('organizer_id', $user->id)
                ->orWhereHas('attendees', function ($att) use ($user) {
                    $att->where('user_id', $user->id);
                });
        });
    }
}
