<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Concerns\FetchesTableFromApi;
use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\VerdanttApiClient;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListRecipes extends ListRecords
{
    use FetchesTableFromApi;

    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function apiModelClass(): string
    {
        return Recipe::class;
    }

    protected function searchableFields(): array
    {
        return ['title', 'cookbook_title', 'keywords'];
    }

    protected function defaultSortColumn(): string
    {
        return 'title';
    }

    protected function fetchAllFromApi(): Collection
    {
        $client = app(VerdanttApiClient::class);
        $page = 1;
        $limit = 100;
        $all = collect();

        do {
            $response = $client->get('/admin/recipes', ['page' => $page, 'limit' => $limit]);
            $recipes = $response->json('data.recipes') ?? [];
            $totalPages = $response->json('data.meta.totalPages') ?? 1;

            $all = $all->concat($recipes);
            $page++;
        } while ($page <= $totalPages);

        return $all;
    }
}
