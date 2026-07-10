<?php

namespace App\Filament\Concerns;

use App\Support\ApiTableData;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Sources a Filament table's records directly from the Verdantt API instead
 * of an Eloquent query — nothing here is ever persisted to a database.
 */
trait FetchesTableFromApi
{
    abstract protected function apiModelClass(): string;

    /**
     * @return array<int, string>
     */
    abstract protected function searchableFields(): array;

    abstract protected function defaultSortColumn(): string;

    /**
     * Fetch every record from the API as plain arrays. Override this for
     * endpoints that paginate server-side and need multiple requests to
     * gather the full set.
     */
    protected function fetchAllFromApi(): Collection
    {
        return collect();
    }

    protected function applyTableFiltersToApiItems(Collection $items): Collection
    {
        return $items;
    }

    public function getTableRecords(): Paginator
    {
        $modelClass = $this->apiModelClass();

        $items = $this->fetchAllFromApi()->map(fn (array $item) => $modelClass::fromApi($item));

        return $this->cachedTableRecords = ApiTableData::paginate(
            $items,
            $this->getTableSearch(),
            $this->searchableFields(),
            $this->getTableSortColumn() ?? $this->defaultSortColumn(),
            $this->getTableSortDirection() ?? 'asc',
            (int) ($this->getTableRecordsPerPage() ?: 10),
            $this->getTablePage(),
            fn (Collection $items) => $this->applyTableFiltersToApiItems($items),
        );
    }

    public function getTableRecord(?string $key): ?Model
    {
        if ($key === null) {
            return null;
        }

        $modelClass = $this->apiModelClass();

        $item = $this->fetchAllFromApi()->first(
            fn (array $i) => (string) ($i['id'] ?? '') === (string) $key
        );

        return $item ? $modelClass::fromApi($item) : null;
    }
}
