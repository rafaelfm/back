<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Notifications\TravelRequestStatusChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class TravelRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TravelRequest::class);

        $user = $request->user();

        $travelRequests = TravelRequest::query()
            ->when(! $user->can('travel.manage'), function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('destination'), function (Builder $query) use ($request): void {
                $destination = strtolower($request->string('destination')->value());
                $query->whereRaw('LOWER(destination) LIKE ?', ["%{$destination}%"]);
            })
            ->when($request->filled('from'), function (Builder $query) use ($request): void {
                $query->whereDate('departure_date', '>=', $request->date('from'));
            })
            ->when($request->filled('to'), function (Builder $query) use ($request): void {
                $query->whereDate('return_date', '<=', $request->date('to'));
            })
            ->latest()
            ->paginate(perPage: $request->integer('per_page', 15));

        return JsonResource::collection($travelRequests)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TravelRequest::class);

        $data = $request->validate([
            'requester_name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after:departure_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $travelRequest = $request->user()->travelRequests()->create([
            ...$data,
            'status' => 'requested',
        ]);

        return JsonResource::make($travelRequest->fresh())->response()->setStatusCode(201);
    }

    public function show(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('view', $travelRequest);

        return JsonResource::make($travelRequest)->response();
    }

    public function update(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('update', $travelRequest);

        $data = $request->validate([
            'requester_name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after:departure_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $travelRequest->update($data);

        return JsonResource::make($travelRequest->fresh())->response();
    }

    public function destroy(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('delete', $travelRequest);

        $travelRequest->delete();

        return response()->json(status: 204);
    }

    public function updateStatus(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['approved', 'cancelled']),
            ],
        ]);

        $this->authorize('updateStatus', [$travelRequest, $validated['status']]);

        if ($travelRequest->status === 'approved' && $validated['status'] === 'cancelled') {
            return response()->json([
                'message' => 'Pedidos aprovados não podem ser cancelados.',
            ], 422);
        }

        $previousStatus = $travelRequest->status;

        if ($travelRequest->status === $validated['status']) {
            return JsonResource::make($travelRequest)->response();
        }

        $travelRequest->update(['status' => $validated['status']]);
        $travelRequest->refresh();

        Notification::send(
            $travelRequest->user,
            new TravelRequestStatusChanged($travelRequest, $previousStatus),
        );

        return JsonResource::make($travelRequest)->response();
    }
}
