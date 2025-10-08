<?php

namespace App\Policies;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if ($user->can('travel.manage') || $user->can('travel.create')) {
            return true;
        }

        // Usuários autenticados ainda podem listar os próprios pedidos.
        return $user->exists;
    }

    public function view(User $user, TravelRequest $travelRequest): bool
    {
        if ($user->can('travel.manage')) {
            return true;
        }

        return $travelRequest->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('travel.create') || $user->can('travel.manage');
    }

    public function update(User $user, TravelRequest $travelRequest): bool
    {
        if ($user->can('travel.manage')) {
            return true;
        }

        return $travelRequest->user_id === $user->id && $travelRequest->status === 'requested';
    }

    public function updateStatus(User $user, TravelRequest $travelRequest, string $status): bool
    {
        if (! $user->can('travel.manage')) {
            return false;
        }

        if ($travelRequest->status === 'approved' && $status === 'cancelled') {
            return false;
        }

        if ($travelRequest->status === 'cancelled' && $status === 'approved') {
            return false;
        }

        return true;
    }

    public function delete(User $user, TravelRequest $travelRequest): bool
    {
        if ($user->can('travel.manage')) {
            return true;
        }

        return $travelRequest->user_id === $user->id && $travelRequest->status !== 'approved';
    }
}
