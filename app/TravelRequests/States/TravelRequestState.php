<?php

namespace App\TravelRequests\States;

use App\Models\TravelRequest;
use App\Models\User;
use App\TravelRequests\Exceptions\InvalidTravelRequestTransitionException;
use App\TravelRequests\Exceptions\TravelRequestTransitionException;
use App\TravelRequests\Exceptions\UnauthorizedTravelRequestTransitionException;

abstract class TravelRequestState
{
    public function __construct(
        protected readonly TravelRequest $travelRequest,
        protected readonly User $actor,
    ) {
    }

    abstract public function name(): string;

    public function ensureTransitionPossible(string $status): void
    {
        $this->assertTransitionPossible($status);
    }

    public function canTransitionTo(string $status): bool
    {
        try {
            $this->assertActorAuthorized($status);

            return true;
        } catch (TravelRequestTransitionException $exception) {
            return false;
        }
    }

    /**
     * @throws TravelRequestTransitionException
     */
    public function transitionTo(string $status): TravelRequest
    {
        $this->assertActorAuthorized($status);

        $this->travelRequest->forceFill(['status' => $status])->save();

        return $this->travelRequest->refresh();
    }

    /**
     * @throws InvalidTravelRequestTransitionException
     */
    protected function assertTransitionPossible(string $status): void
    {
        throw InvalidTravelRequestTransitionException::becauseTransitionIsNotDefined(
            $this->travelRequest,
            $this->name(),
            $status,
        );
    }

    /**
     * @throws TravelRequestTransitionException
     */
    protected function assertActorAuthorized(string $status): void
    {
        $this->assertTransitionPossible($status);

        throw UnauthorizedTravelRequestTransitionException::becauseActorLacksPermission(
            $this->travelRequest,
            $this->name(),
            $status,
        );
    }
}

