<?php

namespace App\Filament\Resources\RemoteUserResource\Pages;

use App\Filament\Concerns\FetchesTableFromApi;
use App\Filament\Resources\RemoteUserResource;
use App\Models\RemoteUser;
use App\Services\VerdanttApiClient;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Collection;

class ManageRemoteUsers extends ManageRecords
{
    use FetchesTableFromApi;

    protected static string $resource = RemoteUserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function apiModelClass(): string
    {
        return RemoteUser::class;
    }

    protected function searchableFields(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    protected function defaultSortColumn(): string
    {
        return 'created_at';
    }

    protected function fetchAllFromApi(): Collection
    {
        $client = app(VerdanttApiClient::class);
        $page = 1;
        $limit = 100;
        $all = collect();

        do {
            $response = $client->get('/admin/users', ['page' => $page, 'limit' => $limit]);
            $users = $response->json('data.users') ?? [];
            $totalPages = $response->json('data.meta.totalPages') ?? 1;

            $all = $all->concat($users);
            $page++;
        } while ($page <= $totalPages);

        return $all;
    }

    protected function applyTableFiltersToApiItems(Collection $items): Collection
    {
        $deletedState = $this->getTableFilterState('is_deleted');
        $roleState = $this->getTableFilterState('role');

        if (($deletedState['value'] ?? null) !== null) {
            $items = $items->filter(fn (RemoteUser $item) => $item->is_deleted === (bool) $deletedState['value']);
        }

        if (filled($roleState['value'] ?? null)) {
            $items = $items->filter(fn (RemoteUser $item) => $item->role === $roleState['value']);
        }

        return $items;
    }
}
