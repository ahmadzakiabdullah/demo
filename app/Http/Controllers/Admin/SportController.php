<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SportGender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSportRequest;
use App\Http\Requests\Admin\UpdateSportRequest;
use App\Models\Event;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportDiscipline;
use App\Models\SportDivision;
use App\Services\SportStructureService;
use App\Support\SportTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SportController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Sport::class);

        $sports = $event->sports()
            ->withCount(['disciplines'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Sport $sport) => [
                'id' => $sport->id,
                'name' => $sport->name,
                'slug' => $sport->slug,
                'status' => $sport->status->value,
                'template_slug' => $sport->template_slug,
                'disciplines_count' => $sport->disciplines_count,
            ]);

        return Inertia::render('Admin/Events/Sports/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'sports' => $sports,
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(Event $event): Response
    {
        $this->authorize('create', [Sport::class, $event]);

        return Inertia::render('Admin/Events/Sports/Create', [
            'event' => $event->only(['id', 'name', 'slug']),
            'templates' => collect(SportTemplates::all())->map(fn ($template) => [
                'slug' => $template['slug'],
                'name' => $template['name'],
            ])->values(),
            'statuses' => \App\Enums\SportStatus::values(),
        ]);
    }

    public function store(
        StoreSportRequest $request,
        Event $event,
        SportStructureService $structureService,
    ): RedirectResponse {
        $template = $request->filled('template_slug')
            ? SportTemplates::find($request->string('template_slug')->toString())
            : null;

        $name = $request->validated('name') ?: $template['name'] ?? 'Sport';
        $slug = $request->validated('slug') ?: $this->uniqueSlug($name, $event->id);

        $sport = Sport::create([
            'event_id' => $event->id,
            'name' => $name,
            'slug' => $slug,
            'template_slug' => $request->validated('template_slug'),
            'status' => $request->validated('status'),
            'rules' => $request->validated('rules') ?? $template['rules'] ?? null,
        ]);

        if ($template !== null) {
            $structureService->applyTemplate($sport, $template['slug']);
        }

        return redirect()->route('admin.events.sports.show', [$event, $sport])
            ->with('success', 'Sport created successfully.');
    }

    public function show(Event $event, Sport $sport): Response
    {
        $this->authorize('view', $sport);

        $sport->load([
            'disciplines.categories.divisions',
        ]);

        return Inertia::render('Admin/Events/Sports/Show', [
            'event' => $event->only(['id', 'name', 'slug']),
            'sport' => $this->sportPayload($sport),
            'genders' => SportGender::values(),
        ]);
    }

    public function edit(Event $event, Sport $sport): Response
    {
        $this->authorize('update', $sport);

        return Inertia::render('Admin/Events/Sports/Edit', [
            'event' => $event->only(['id', 'name', 'slug']),
            'sport' => [
                'id' => $sport->id,
                'name' => $sport->name,
                'slug' => $sport->slug,
                'status' => $sport->status->value,
                'rules' => $sport->rules,
            ],
            'statuses' => \App\Enums\SportStatus::values(),
        ]);
    }

    public function update(UpdateSportRequest $request, Event $event, Sport $sport): RedirectResponse
    {
        $sport->update($request->validated());

        return redirect()->route('admin.events.sports.show', [$event, $sport])
            ->with('success', 'Sport updated successfully.');
    }

    public function destroy(Event $event, Sport $sport): RedirectResponse
    {
        $this->authorize('delete', $sport);

        $sport->delete();

        return redirect()->route('admin.events.sports.index', $event)
            ->with('success', 'Sport deleted successfully.');
    }

    public function storeDiscipline(Request $request, Event $event, Sport $sport): RedirectResponse
    {
        $this->authorize('update', $sport);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $slug = $this->uniqueDisciplineSlug($validated['name'], $sport->id);

        SportDiscipline::query()->create([
            'sport_id' => $sport->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'sort_order' => $sport->disciplines()->count(),
        ]);

        return back()->with('success', 'Discipline added.');
    }

    public function destroyDiscipline(Event $event, Sport $sport, SportDiscipline $discipline): RedirectResponse
    {
        $this->authorize('update', $sport);
        abort_unless($discipline->sport_id === $sport->id, 404);

        $discipline->delete();

        return back()->with('success', 'Discipline removed.');
    }

    public function storeCategory(Request $request, Event $event, Sport $sport, SportDiscipline $discipline): RedirectResponse
    {
        $this->authorize('update', $sport);
        abort_unless($discipline->sport_id === $sport->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(SportGender::class)],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:min_age'],
        ]);

        SportCategory::query()->create([
            'sport_discipline_id' => $discipline->id,
            'name' => $validated['name'],
            'slug' => $this->uniqueCategorySlug($validated['name'], $discipline->id),
            'gender' => $validated['gender'],
            'min_age' => $validated['min_age'] ?? null,
            'max_age' => $validated['max_age'] ?? null,
            'sort_order' => $discipline->categories()->count(),
        ]);

        return back()->with('success', 'Category added.');
    }

    public function destroyCategory(
        Event $event,
        Sport $sport,
        SportDiscipline $discipline,
        SportCategory $category,
    ): RedirectResponse {
        $this->authorize('update', $sport);
        abort_unless($category->sport_discipline_id === $discipline->id && $discipline->sport_id === $sport->id, 404);

        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    public function storeDivision(
        Request $request,
        Event $event,
        Sport $sport,
        SportDiscipline $discipline,
        SportCategory $category,
    ): RedirectResponse {
        $this->authorize('update', $sport);
        abort_unless($category->sport_discipline_id === $discipline->id && $discipline->sport_id === $sport->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        SportDivision::query()->create([
            'sport_category_id' => $category->id,
            'name' => $validated['name'],
            'slug' => $this->uniqueDivisionSlug($validated['name'], $category->id),
            'sort_order' => $category->divisions()->count(),
        ]);

        return back()->with('success', 'Division added.');
    }

    public function destroyDivision(
        Event $event,
        Sport $sport,
        SportDiscipline $discipline,
        SportCategory $category,
        SportDivision $division,
    ): RedirectResponse {
        $this->authorize('update', $sport);
        abort_unless(
            $division->sport_category_id === $category->id
            && $category->sport_discipline_id === $discipline->id
            && $discipline->sport_id === $sport->id,
            404,
        );

        $division->delete();

        return back()->with('success', 'Division removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function sportPayload(Sport $sport): array
    {
        return [
            'id' => $sport->id,
            'name' => $sport->name,
            'slug' => $sport->slug,
            'status' => $sport->status->value,
            'template_slug' => $sport->template_slug,
            'rules' => $sport->rules,
            'disciplines' => $sport->disciplines->map(fn (SportDiscipline $discipline) => [
                'id' => $discipline->id,
                'name' => $discipline->name,
                'slug' => $discipline->slug,
                'categories' => $discipline->categories->map(fn (SportCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'gender' => $category->gender->value,
                    'min_age' => $category->min_age,
                    'max_age' => $category->max_age,
                    'divisions' => $category->divisions->map(fn (SportDivision $division) => [
                        'id' => $division->id,
                        'name' => $division->name,
                        'slug' => $division->slug,
                    ]),
                ]),
            ]),
        ];
    }

    private function uniqueSlug(string $name, int $eventId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Sport::withTrashed()->where('event_id', $eventId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function uniqueDisciplineSlug(string $name, int $sportId): string
    {
        return $this->uniqueChildSlug($name, SportDiscipline::class, 'sport_id', $sportId);
    }

    private function uniqueCategorySlug(string $name, int $disciplineId): string
    {
        return $this->uniqueChildSlug($name, SportCategory::class, 'sport_discipline_id', $disciplineId);
    }

    private function uniqueDivisionSlug(string $name, int $categoryId): string
    {
        return $this->uniqueChildSlug($name, SportDivision::class, 'sport_category_id', $categoryId);
    }

    private function uniqueChildSlug(string $name, string $model, string $column, int $parentId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while ($model::query()->where($column, $parentId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}