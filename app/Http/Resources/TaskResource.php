<?php

namespace App\Http\Resources;

use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $createdUser = $this->user;
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'status' => $this->status,
            'priority' => $this->priority,
            'estimated_time' => $this->estimated_time ?? null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'project_id' => $this->project_id,
            'created_user' => $createdUser ? [
                'id' => $createdUser->id,
                'name' => $createdUser->name,
                'email' => $createdUser->email,
                'avatar' => AvatarService::getAvatarUrl($createdUser),
            ] : [],
            'assigned_users' => $this->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => AvatarService::getAvatarUrl($user),
                    'assigned_time' => isset($user->pivot) ? $user->pivot->created_at->format('Y-m-d H:i:s') : [],
                ];
            }),
        ];
    }
}
