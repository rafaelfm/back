<?php

namespace App\TravelRequests\Exceptions;

use App\Models\TravelRequest;
use RuntimeException;

class TravelRequestTransitionException extends RuntimeException
{
    public function __construct(
        protected readonly TravelRequest $travelRequest,
        protected readonly string $from,
        protected readonly string $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function travelRequest(): TravelRequest
    {
        return $this->travelRequest;
    }

    public function from(): string
    {
        return $this->from;
    }

    public function to(): string
    {
        return $this->to;
    }
}

