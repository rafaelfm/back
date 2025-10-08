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

        $locationTerm = null;

        if ($request->filled('location')) {
            $locationTerm = strtolower($request->string('location')->value());
        } elseif ($request->filled('destination')) {
            $locationTerm = strtolower($request->string('destination')->value());
        }

        $travelRequests = TravelRequest::query()
            ->with(['city.state', 'city.country', 'user'])
            ->when(! $user->can('travel.manage'), function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('status', $request->string('status'));
            })
            ->when($locationTerm !== null && $locationTerm !== '', function (Builder $query) use ($locationTerm): void {
                $tokens = collect(preg_split('/[\s,]+/', $locationTerm, -1, PREG_SPLIT_NO_EMPTY));

                $query->whereHas('city', function (Builder $locationQuery) use ($tokens): void {
                    $tokens->each(function (string $token) use ($locationQuery): void {
                        $like = "%{$token}%";

                        $locationQuery->where(function (Builder $inner) use ($like): void {
                            $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                                ->orWhereHas('state', function (Builder $stateQuery) use ($like): void {
                                    $stateQuery->whereRaw('LOWER(name) LIKE ?', [$like])
                                        ->orWhereRaw('LOWER(code) LIKE ?', [$like]);
                                })
                                ->orWhereHas('country', function (Builder $countryQuery) use ($like): void {
                                    $countryQuery->whereRaw('LOWER(name) LIKE ?', [$like]);
                                });
                        });
                    });
                });
            })
            ->when($request->filled('departure_from'), function (Builder $query) use ($request): void {
                $query->whereDate('departure_date', '>=', $request->date('departure_from'));
            })
            ->when($request->filled('departure_to'), function (Builder $query) use ($request): void {
                $query->whereDate('departure_date', '<=', $request->date('departure_to'));
            })
            ->when($request->filled('return_from'), function (Builder $query) use ($request): void {
                $query->whereDate('return_date', '>=', $request->date('return_from'));
            })
            ->when($request->filled('return_to'), function (Builder $query) use ($request): void {
                $query->whereDate('return_date', '<=', $request->date('return_to'));
            })
            ->latest()
            ->paginate(perPage: $request->integer('per_page', 10));

        return JsonResource::collection($travelRequests)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TravelRequest::class);

        $data = $request->validate(
            $this->validationRules(),
            $this->validationMessages(),
            $this->validationAttributes(),
        );

        $travelRequest = $request->user()->travelRequests()->create([
            'city_id' => $data['city_id'],
            'requester_name' => $data['requester_name'],
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'notes' => $data['notes'] ?? null,
            'status' => 'requested',
        ]);

        $travelRequest->load(['city.state', 'city.country']);

        return JsonResource::make($travelRequest)->response()->setStatusCode(201);
    }

    public function show(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('view', $travelRequest);

        $travelRequest->load(['city.state', 'city.country']);

        return JsonResource::make($travelRequest)->response();
    }

    public function update(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('update', $travelRequest);

        $data = $request->validate(
            $this->validationRules(),
            $this->validationMessages(),
            $this->validationAttributes(),
        );

        $travelRequest->update([
            'city_id' => $data['city_id'],
            'requester_name' => $data['requester_name'],
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        $travelRequest->load(['city.state', 'city.country']);

        return JsonResource::make($travelRequest)->response();
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
        $travelRequest->refresh()->load(['city.state', 'city.country']);

        Notification::send(
            $travelRequest->user,
            new TravelRequestStatusChanged($travelRequest, $previousStatus),
        );

        return JsonResource::make($travelRequest)->response();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validationRules(): array
    {
        return [
            'requester_name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after:departure_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto válido.',
            'max.string' => 'O campo :attribute deve ter no máximo :max caracteres.',
            'date' => 'O campo :attribute deve ser uma data válida.',
            'departure_date.after_or_equal' => 'O campo :attribute deve ser uma data a partir de hoje.',
            'return_date.after' => 'O campo :attribute deve ser uma data posterior à Data de ida.',
            'city_id.required' => 'Selecione uma cidade válida.',
            'city_id.integer' => 'Selecione uma cidade válida.',
            'city_id.exists' => 'Selecione uma cidade válida.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'requester_name' => 'Solicitante',
            'city_id' => 'Cidade',
            'departure_date' => 'Data de ida',
            'return_date' => 'Data de volta',
            'notes' => 'Observações',
        ];
    }
}
