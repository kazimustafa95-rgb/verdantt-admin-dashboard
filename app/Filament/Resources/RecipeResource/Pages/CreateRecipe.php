<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\VerdanttApiClient;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateRecipe extends CreateRecord
{
    protected static string $resource = RecipeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $client = app(VerdanttApiClient::class);

        $response = $client->postMultipart(
            '/admin/recipes',
            RecipeResource::apiFields($data),
            ['image' => $data['image'] ?? null],
        );

        if (! $response->successful()) {
            Notification::make()
                ->title('Failed to create recipe')
                ->body($response->json('message') ?? 'The API request failed.')
                ->danger()
                ->send();

            throw new Halt();
        }

        $item = $response->json('data') ?? $response->json();

        return Recipe::fromApi($item);
    }
}
