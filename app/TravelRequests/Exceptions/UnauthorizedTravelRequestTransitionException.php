<?php

namespace App\TravelRequests\Exceptions;

use App\Models\TravelRequest;

class UnauthorizedTravelRequestTransitionException extends TravelRequestTransitionException
{
    public static function becauseActorLacksPermission(
        TravelRequest $travelRequest,
        string $from,
        string $to,
    ): self {
        return new self(
            $travelRequest,
            $from,
            $to,
            sprintf('Você não tem permissão para mudar de "%s" para "%s".', $from, $to),
        );
    }
}

