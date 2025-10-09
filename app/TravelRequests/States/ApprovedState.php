<?php

namespace App\TravelRequests\States;

class ApprovedState extends TravelRequestState
{
    public function name(): string
    {
        return 'approved';
    }
}

