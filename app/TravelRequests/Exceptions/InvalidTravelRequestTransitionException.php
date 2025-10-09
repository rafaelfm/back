<?php

namespace App\TravelRequests\Exceptions;

use App\Models\TravelRequest;

class InvalidTravelRequestTransitionException extends TravelRequestTransitionException
{
    public static function becauseTransitionIsNotDefined(
        TravelRequest $travelRequest,
        string $from,
        string $to,
    ): self {
        return new self(
            $travelRequest,
            $from,
            $to,
            sprintf('A transição de "%s" para "%s" não é permitida.', $from, $to),
        );
    }
}

