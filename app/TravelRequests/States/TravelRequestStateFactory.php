<?php

namespace App\TravelRequests\States;

use App\Models\TravelRequest;
use App\Models\User;
use InvalidArgumentException;

class TravelRequestStateFactory
{
    public static function make(TravelRequest $travelRequest, User $actor): TravelRequestState
    {
        return match ($travelRequest->status) {
            'requested' => new RequestedState($travelRequest, $actor),
            'approved' => new ApprovedState($travelRequest, $actor),
            'cancelled' => new CancelledState($travelRequest, $actor),
            default => throw new InvalidArgumentException(sprintf(
                'Estado "%s" não é suportado.',
                $travelRequest->status,
            )),
        };
    }
}

