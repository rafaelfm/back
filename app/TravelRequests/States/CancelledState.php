<?php

namespace App\TravelRequests\States;

class CancelledState extends TravelRequestState
{
    public function name(): string
    {
        return 'cancelled';
    }
}

