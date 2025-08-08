<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'estimated_time',
        'created_by',
        'assigned_manager_id',
        'project_id',
    ];

    /**
     * Get the user that created the task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /**
     * Get the project that owns the Task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this
            ->belongsTo(Project::class, 'project_id', 'id');
    }
    public function user(): BelongsTo
    {
        return $this
            ->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * The users that belong to the task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }
    public function comments(): HasMany
    {
        return $this
            ->hasMany(Comment::class, 'task_id', 'id');
    }

    public function assignedManager(): BelongsTo
    {
        return $this
            ->belongsTo(User::class, 'assigned_manager_id');
    }


}
