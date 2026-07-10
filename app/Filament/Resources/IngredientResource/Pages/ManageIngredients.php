<?php

namespace App\Filament\Resources\IngredientResource\Pages;

use App\Filament\Concerns\FetchesTableFromApi;
use App\Filament\Resources\IngredientResource;
use App\Models\Ingredient;
use App\Services\VerdanttApiClient;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Collection;

class ManageIngredients extends ManageRecords
{
    use FetchesTableFromApi;

    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function apiModelClass(): string
    {
        return Ingredient::class;
    }

    protected function searchableFields(): array
    {
        return ['name', 'category'];
    }

    protected function defaultSortColumn(): string
    {
        return 'name';
    }

    protected function fetchAllFromApi(): Collection
    {
        return collect(app(VerdanttApiClient::class)->get('/admin/ingredients')->json('data') ?? []);
    }

    protected function applyTableFiltersToApiItems(Collection $items): Collection
    {
        $state = $this->getTableFilterState('is_produce');

        if (($state['value'] ?? null) !== null) {
            $items = $items->filter(fn (Ingredient $item) => $item->is_produce === (bool) $state['value']);
        }

        return $items;
    }
}
