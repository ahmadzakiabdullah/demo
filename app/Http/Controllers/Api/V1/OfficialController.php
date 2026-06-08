<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfficialRequest;
use App\Http\Requests\Admin\UpdateOfficialRequest;
use App\Http\Resources\Api\V1\OfficialResource;
use App\Models\Event;
use App\Models\Official;
use App\Models\Registration;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficialController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Official::class);

        $officials = Official::query()
            ->where('organization_id', $event->organization_id)
            ->whereHas('registrations', fn ($query) => $query->where('event_id', $event->id))
            ->with([
                'registrations' => fn ($query) => $query
                    ->where('event_id', $event->id)
                    ->with(['sport', 'sportCategory', 'sportDivision']),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request, $event) {
                $query->whereHas('registrations', fn ($registrationQuery) => $registrationQuery
                    ->where('event_id', $event->id)
                    ->where('status', $request->string('status')->toString()));
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->string('type')->toString());
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($officials, OfficialResource::class);
    }

    public function store(StoreOfficialRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();

        $official = $validated['existing_official_id'] ?? null
            ? Official::query()->findOrFail($validated['existing_official_id'])
            : Official::create([
                'organization_id' => $event->organization_id,
                'user_id' => $validated['user_id'] ?? null,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'type' => $validated['type'],
                'certification_level' => $validated['certification_level'] ?? null,
                'certification_expires_at' => $validated['certification_expires_at'] ?? null,
            ]);

        Registration::create([
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'registrable_type' => Official::class,
            'registrable_id' => $official->id,
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'sport_division_id' => $validated['sport_division_id'] ?? null,
            'status' => RegistrationStatus::Draft,
            'notes' => $validated['notes'] ?? null,
        ]);

        $official->load([
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sport', 'sportCategory', 'sportDivision']),
        ]);

        return ApiResponse::success(new OfficialResource($official), 'Official registered.', 201);
    }

    public function show(Event $event, Official $official): JsonResponse
    {
        $this->authorize('view', $official);
        abort_unless($official->organization_id === $event->organization_id, 404);

        $official->load([
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sport', 'sportCategory', 'sportDivision']),
        ]);

        $official->setRelation(
            'history',
            $official->registrations()
                ->with(['event:id,name,slug', 'sport:id,name'])
                ->where('event_id', '!=', $event->id)
                ->orderByDesc('created_at')
                ->get(),
        );

        abort_unless($official->registrations->isNotEmpty(), 404);

        return ApiResponse::success(new OfficialResource($official));
    }

    public function update(UpdateOfficialRequest $request, Event $event, Official $official): JsonResponse
    {
        abort_unless($official->organization_id === $event->organization_id, 404);

        $official->update($request->validated());

        $official->load([
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sport', 'sportCategory', 'sportDivision']),
        ]);

        return ApiResponse::success(new OfficialResource($official), 'Official updated.');
    }

    public function destroy(Event $event, Official $official): JsonResponse
    {
        $this->authorize('delete', $official);
        abort_unless($official->organization_id === $event->organization_id, 404);

        $official->delete();

        return ApiResponse::success(message: 'Official deleted.');
    }
}