<?php

namespace App\Filament\Pages;

use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Models\User;
use App\Support\NewsroomHomeCompositionService;
use App\Support\NewsroomHomePlacementService;
use BackedEnum;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;
use UnitEnum;

class NewsroomHomeComposer extends Page
{
    public string $previewAt = '';

    /**
     * @var array<string, array{
     *     placement_id: ?int,
     *     edit_token: ?string,
     *     article_id: ?int,
     *     starts_at: ?string,
     *     ends_at: ?string
     * }>
     */
    public array $slots = [];

    /** @var array<string, string> */
    public array $searches = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 35;

    protected static ?string $title = 'Układ aktualności';

    protected string $view = 'filament.pages.newsroom-home-composer';

    protected static ?string $slug = 'newsroom-home-composer';

    public static function getNavigationLabel(): string
    {
        return 'Układ aktualności';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Układ /aktualnosci';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Stałe sloty redakcyjne, fallbacki i prywatny podgląd stanu strony w wybranym czasie.';
    }

    public function mount(): void
    {
        $this->previewAt = now()
            ->timezone((string) config('app.timezone', 'Europe/Warsaw'))
            ->format('Y-m-d\TH:i');

        $this->loadSlots();
    }

    public function updatedPreviewAt(): void
    {
        try {
            $this->previewTime();
        } catch (Throwable) {
            return;
        }

        $this->loadSlots();
    }

    public function chooseArticle(string $slotId, int $articleId): void
    {
        if (! array_key_exists($slotId, $this->slotDefinitionMap())) {
            return;
        }

        $article = ContentArticle::query()->find($articleId);

        if ($article === null) {
            return;
        }

        $this->slots[$slotId]['article_id'] = (int) $article->getKey();
        $this->searches[$slotId] = (string) $article->title;
    }

    public function clearSelection(string $slotId): void
    {
        if (! isset($this->slots[$slotId])) {
            return;
        }

        $this->slots[$slotId]['article_id'] = null;
        $this->searches[$slotId] = '';
    }

    public function saveSlot(string $slotId): void
    {
        $definition = $this->slotDefinitionMap()[$slotId] ?? null;
        $state = $this->slots[$slotId] ?? null;

        if ($definition === null || $state === null) {
            return;
        }

        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->isAdministrator(), 403);

        $articleId = (int) ($state['article_id'] ?? 0);
        $article = ContentArticle::query()->find($articleId);

        if ($article === null) {
            $this->danger('Wybierz artykuł przed zapisem.');

            return;
        }

        $previewAt = $this->previewTime();

        if (! $this->compositionService()->isArticleEligibleAt($article, $previewAt)) {
            $this->danger('Wybrany artykuł nie będzie kwalifikowany do dystrybucji w ustawionym czasie podglądu.');

            return;
        }

        $attributes = [
            'surface_key' => ContentHomePlacement::SURFACE_NEWSROOM_HOME,
            'slot_key' => $definition['slot_key'],
            'context_key' => $definition['context_key'],
            'position' => $definition['position'],
            'article_id' => $articleId,
            'starts_at' => $state['starts_at'] ?: null,
            'ends_at' => $state['ends_at'] ?: null,
        ];

        try {
            if ($state['placement_id']) {
                $placement = ContentHomePlacement::query()->findOrFail((int) $state['placement_id']);

                $this->placementService()->update(
                    $placement,
                    $attributes,
                    (string) ($state['edit_token'] ?? ''),
                    $actor,
                );
            } else {
                $this->placementService()->create($attributes, $actor);
            }
        } catch (DomainException|InvalidArgumentException|ModelNotFoundException $exception) {
            $this->danger($exception->getMessage());

            return;
        }

        $this->loadSlots();

        Notification::make()
            ->title('Placement zapisany')
            ->body('Układ został zapisany z ochroną overlap i stale-write.')
            ->success()
            ->send();
    }

    public function removePlacement(string $slotId): void
    {
        $state = $this->slots[$slotId] ?? null;

        if ($state === null || ! $state['placement_id']) {
            return;
        }

        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->isAdministrator(), 403);

        try {
            $placement = ContentHomePlacement::query()->findOrFail((int) $state['placement_id']);

            $this->placementService()->delete(
                $placement,
                (string) ($state['edit_token'] ?? ''),
                $actor,
            );
        } catch (DomainException|ModelNotFoundException $exception) {
            $this->danger($exception->getMessage());

            return;
        }

        $this->loadSlots();

        Notification::make()
            ->title('Ręczne przypisanie usunięte')
            ->body('Slot wrócił do deterministycznego fallbacku.')
            ->success()
            ->send();
    }

    public function refreshComposer(): void
    {
        $this->loadSlots();

        Notification::make()
            ->title('Composer odświeżony')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $definitions = $this->slotDefinitions();
        $previewAt = $this->previewTime();

        $selectedIds = collect($this->slots)
            ->pluck('article_id')
            ->filter(fn (mixed $id): bool => (int) $id > 0)
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        $selectedArticles = ContentArticle::query()
            ->with('category:id,name,slug')
            ->whereIn('id', $selectedIds->all())
            ->get()
            ->keyBy('id');

        $duplicateIds = $selectedIds
            ->countBy()
            ->filter(fn (int $count): bool => $count > 1)
            ->keys()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $fallbackComposition = $this->compositionService()->compose(
            $previewAt,
            includeManualPlacements: false,
        );

        $rows = [];

        foreach ($definitions as $definition) {
            $slotId = $definition['id'];
            $state = $this->slots[$slotId] ?? [
                'placement_id' => null,
                'edit_token' => null,
                'article_id' => null,
                'starts_at' => null,
                'ends_at' => null,
            ];

            /** @var ContentArticle|null $selectedArticle */
            $selectedArticle = $state['article_id']
                ? $selectedArticles->get((int) $state['article_id'])
                : null;

            $rows[] = [
                ...$definition,
                'state' => $state,
                'selected_article' => $selectedArticle,
                'eligible' => $selectedArticle === null
                    ? null
                    : $this->compositionService()->isArticleEligibleAt($selectedArticle, $previewAt),
                'duplicate' => $selectedArticle !== null
                    && in_array((int) $selectedArticle->getKey(), $duplicateIds, true),
                'fallback_article' => $this->fallbackFor($definition, $fallbackComposition),
                'search_results' => $this->searchResults($slotId),
            ];
        }

        return [
            'rows' => $rows,
            'previewUrl' => route('admin.newsroom.home-preview', [
                'at' => $previewAt->format('Y-m-d H:i:s'),
            ]),
            'previewAtLabel' => $previewAt
                ->timezone((string) config('app.timezone', 'Europe/Warsaw'))
                ->format('d.m.Y H:i T'),
        ];
    }

    protected function loadSlots(): void
    {
        $previewAt = $this->previewTime();
        $placements = ContentHomePlacement::query()
            ->activeAt($previewAt)
            ->where('surface_key', ContentHomePlacement::SURFACE_NEWSROOM_HOME)
            ->get()
            ->keyBy(fn (ContentHomePlacement $placement): string => $this->slotId(
                $placement->slot_key,
                $placement->context_key,
                (int) $placement->position,
            ));

        $state = [];

        foreach ($this->slotDefinitions() as $definition) {
            /** @var ContentHomePlacement|null $placement */
            $placement = $placements->get($definition['id']);

            $state[$definition['id']] = [
                'placement_id' => $placement?->getKey() ? (int) $placement->getKey() : null,
                'edit_token' => $placement ? $this->placementService()->editToken($placement) : null,
                'article_id' => $placement?->article_id ? (int) $placement->article_id : null,
                'starts_at' => $this->localInputDate($placement?->starts_at),
                'ends_at' => $this->localInputDate($placement?->ends_at),
            ];
        }

        $this->slots = $state;
        $this->searches = [];
    }

    /**
     * @return list<array{
     *     id: string,
     *     slot_key: string,
     *     context_key: ?string,
     *     position: int,
     *     label: string,
     *     section: string
     * }>
     */
    protected function slotDefinitions(): array
    {
        $definitions = [[
            'id' => $this->slotId(ContentHomePlacement::SLOT_LEAD, null, 0),
            'slot_key' => ContentHomePlacement::SLOT_LEAD,
            'context_key' => null,
            'position' => 0,
            'label' => 'Lead',
            'section' => 'Główne',
        ]];

        for ($position = 0; $position < NewsroomHomeCompositionService::DEFAULT_SECONDARY_LIMIT; $position++) {
            $definitions[] = [
                'id' => $this->slotId(ContentHomePlacement::SLOT_SECONDARY, null, $position),
                'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
                'context_key' => null,
                'position' => $position,
                'label' => 'Secondary '.($position + 1),
                'section' => 'Drugoplanowe',
            ];
        }

        foreach (ContentCategory::query()->active()->orderBy('position')->orderBy('id')->get(['name', 'slug']) as $category) {
            $definitions[] = [
                'id' => $this->slotId(ContentHomePlacement::SLOT_CATEGORY_LEAD, (string) $category->slug, 0),
                'slot_key' => ContentHomePlacement::SLOT_CATEGORY_LEAD,
                'context_key' => (string) $category->slug,
                'position' => 0,
                'label' => 'Kategoria: '.$category->name,
                'section' => 'Leady kategorii',
            ];
        }

        $definitions[] = [
            'id' => $this->slotId(ContentHomePlacement::SLOT_GUIDES_LEAD, null, 0),
            'slot_key' => ContentHomePlacement::SLOT_GUIDES_LEAD,
            'context_key' => null,
            'position' => 0,
            'label' => 'Poradniki — lead',
            'section' => 'Poradniki',
        ];

        for ($position = 0; $position < NewsroomHomeCompositionService::DEFAULT_IMPORTANT_NOW_LIMIT; $position++) {
            $definitions[] = [
                'id' => $this->slotId(ContentHomePlacement::SLOT_IMPORTANT_NOW, null, $position),
                'slot_key' => ContentHomePlacement::SLOT_IMPORTANT_NOW,
                'context_key' => null,
                'position' => $position,
                'label' => 'Ważne teraz '.($position + 1),
                'section' => 'Ważne teraz',
            ];
        }

        return $definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function slotDefinitionMap(): array
    {
        return collect($this->slotDefinitions())
            ->keyBy('id')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $composition
     */
    protected function fallbackFor(array $definition, array $composition): ?ContentArticle
    {
        return match ($definition['slot_key']) {
            ContentHomePlacement::SLOT_LEAD => $composition['lead'],
            ContentHomePlacement::SLOT_SECONDARY => $composition['secondary'][$definition['position']] ?? null,
            ContentHomePlacement::SLOT_GUIDES_LEAD => $composition['guides']['lead'],
            ContentHomePlacement::SLOT_IMPORTANT_NOW => $composition['important_now'][$definition['position']] ?? null,
            ContentHomePlacement::SLOT_CATEGORY_LEAD => collect($composition['categories'])
                ->first(fn (array $block): bool => (string) $block['category']->slug === $definition['context_key'])['lead'] ?? null,
            default => null,
        };
    }

    /**
     * @return list<array{id:int,title:string,slug:string,status:string,category:?string}>
     */
    protected function searchResults(string $slotId): array
    {
        $search = trim((string) ($this->searches[$slotId] ?? ''));

        if (mb_strlen($search) < 2) {
            return [];
        }

        return ContentArticle::query()
            ->with('category:id,name')
            ->where(function ($query) use ($search): void {
                $query
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            })
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (ContentArticle $article): array => [
                'id' => (int) $article->getKey(),
                'title' => (string) $article->title,
                'slug' => (string) $article->slug,
                'status' => $article->workflow_status?->value ?? (string) $article->workflow_status,
                'category' => $article->category?->name,
            ])
            ->values()
            ->all();
    }

    protected function previewTime(): Carbon
    {
        return Carbon::parse(
            $this->previewAt,
            (string) config('app.timezone', 'Europe/Warsaw'),
        );
    }

    protected function slotId(string $slotKey, ?string $contextKey, int $position): string
    {
        return implode('--', [
            $slotKey,
            $contextKey ?: 'global',
            (string) $position,
        ]);
    }

    protected function localInputDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone((string) config('app.timezone', 'Europe/Warsaw'))
            ->format('Y-m-d\TH:i');
    }

    protected function placementService(): NewsroomHomePlacementService
    {
        return app(NewsroomHomePlacementService::class);
    }

    protected function compositionService(): NewsroomHomeCompositionService
    {
        return app(NewsroomHomeCompositionService::class);
    }

    protected function danger(string $message): void
    {
        Notification::make()
            ->title('Nie zapisano placementu')
            ->body($message)
            ->danger()
            ->persistent()
            ->send();
    }
}
