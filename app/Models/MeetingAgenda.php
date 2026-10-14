<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAgenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'title',
        'duration_minutes',
        'presenter_id',
        'presenter_name',
        'order_index',
        'discussion_notes',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'order_index' => 'integer',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }
}
