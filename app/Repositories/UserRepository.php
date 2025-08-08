<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function createUser($data)
    {
        return $this->user->create($data);
    }

    public function getManagers()
    {
        return $this->user->role('manager')->get();
    }

    public function getUser($email)
    {
        return $this->user->where('email', $email)->first();
    }

    public function getUserById($id)
    {
        return $this->user->findOrFail($id);
    }

    public function isEmailVerified($routeHash, $user)
    {
        return hash_equals(
            (string) $routeHash,
            sha1($user->getEmailForVerification())
        );
    }
}
