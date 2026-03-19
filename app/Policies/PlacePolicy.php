<?php

namespace App\Policies;

use App\Models\Place;
use App\Models\User;

class PlacePolicy
{
    public function view(User $user, Place $place): bool
    {
        return (int) $user->id === (int) $place->user_id;
    }

    public function update(User $user, Place $place): bool
    {
        return (int) $user->id === (int) $place->user_id;
    }

    public function delete(User $user, Place $place): bool
    {
        return (int) $user->id === (int) $place->user_id;
    }

    public function sync(User $user, Place $place): bool
    {
        return (int) $user->id === (int) $place->user_id;
    }
}
