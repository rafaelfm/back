<?php

namespace App\TravelRequests\States;

use App\TravelRequests\Exceptions\UnauthorizedTravelRequestTransitionException;

class RequestedState extends TravelRequestState
{
    public function name(): string
    {
        return 'requested';
    }

    protected function assertTransitionPossible(string $status): void
    {
        if (in_array($status, ['approved', 'cancelled'], true)) {
            return;
        }

        parent::assertTransitionPossible($status);
    }

    protected function assertActorAuthorized(string $status): void
    {
        $this->assertTransitionPossible($status);

        if (! $this->actor->can('travel.manage')) {
            throw UnauthorizedTravelRequestTransitionException::becauseActorLacksPermission(
                $this->travelRequest,
                $this->name(),
                $status,
            );
        }
    }
}

