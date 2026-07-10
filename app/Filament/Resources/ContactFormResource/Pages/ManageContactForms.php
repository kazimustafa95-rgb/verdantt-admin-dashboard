<?php

namespace App\Filament\Resources\ContactFormResource\Pages;

use App\Filament\Concerns\FetchesTableFromApi;
use App\Filament\Resources\ContactFormResource;
use App\Models\ContactForm;
use App\Services\VerdanttApiClient;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Collection;

class ManageContactForms extends ManageRecords
{
    use FetchesTableFromApi;

    protected static string $resource = ContactFormResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function apiModelClass(): string
    {
        return ContactForm::class;
    }

    protected function searchableFields(): array
    {
        return ['name', 'email', 'subject'];
    }

    protected function defaultSortColumn(): string
    {
        return 'created_at';
    }

    protected function fetchAllFromApi(): Collection
    {
        return collect(app(VerdanttApiClient::class)->get('/admin/contact-forms')->json('data') ?? []);
    }

    protected function applyTableFiltersToApiItems(Collection $items): Collection
    {
        $state = $this->getTableFilterState('is_read');

        if (($state['value'] ?? null) !== null) {
            $items = $items->filter(fn (ContactForm $item) => $item->is_read === (bool) $state['value']);
        }

        return $items;
    }
}
