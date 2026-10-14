<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_type',
        'file_name',
        'total_rows',
        'success_rows',
        'failed_rows',
        'errors_json',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'errors_json' => 'array',
            'total_rows' => 'integer',
            'success_rows' => 'integer',
            'failed_rows' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-success',
            'partially_failed' => 'bg-warning text-dark',
            'failed' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
