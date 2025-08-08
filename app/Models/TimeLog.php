<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'hours_spent',
    ];
    /**
     * Get the task that owns the TimeLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
