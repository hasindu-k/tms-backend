<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'created_by',
    ];

    /**
     * Get all of the task for the Project
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function task(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id', 'id');
    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
    }
    /**
     * Get the projects that owns the Project
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function attachUsers(array $userIds)
    {
        $this->users()->attach($userIds);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_id', 'project_id');
    }
}
