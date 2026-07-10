<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\VerdanttApiClient;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EditRecipe extends EditRecord
{
    protected static string $resource = RecipeResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return Recipe::findFromApi($key) ?? throw (new ModelNotFoundException())->setModel(Recipe::class, [$key]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->using(function (Model $record) {
                    $response = app(VerdanttApiClient::class)->delete("/admin/recipes/{$record->id}");

                    if (! $response->successful()) {
                        Notification::make()
                            ->title('Failed to delete recipe')
                            ->body($response->json('message') ?? 'The API request failed.')
                            ->danger()
                            ->send();

                        throw new Halt();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['ingredients'] = collect($data['ingredients'] ?? [])
            ->map(fn (array $row) => [
                'ingredient_id' => $row['ingredient_id'],
                'quantity' => $row['quantity'] ?? null,
                'quantity_label' => $row['quantity_label'] ?? null,
                'unit' => $row['unit'] ?? null,
                'prefix' => $row['prefix'] ?? null,
                'notes' => $row['notes'] ?? null,
                'is_optional' => (bool) ($row['is_optional'] ?? false),
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $client = app(VerdanttApiClient::class);

        $response = $client->postMultipart(
            "/admin/recipes/{$record->id}",
            RecipeResource::apiFields($data),
            ['image' => $data['image'] ?? null],
            'PUT',
        );

        if (! $response->successful()) {
            Notification::make()
                ->title('Failed to update recipe')
                ->body($response->json('message') ?? 'The API request failed.')
                ->danger()
                ->send();

            throw new Halt();
        }

        $item = $response->json('data') ?? $response->json();

        return Recipe::fromApi($item);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
