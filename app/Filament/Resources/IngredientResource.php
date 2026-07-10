<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\FetchesTableFromApi;
use App\Filament\Resources\IngredientResource\Pages;
use App\Models\Ingredient;
use App\Services\VerdanttApiClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('category')
                    ->maxLength(255),
                Forms\Components\TextInput::make('image_url')
                    ->label('Image URL')
                    ->url()
                    ->maxLength(500),
                Forms\Components\Toggle::make('is_produce')
                    ->label('Is produce'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=?&background=1F3A2E&color=fff'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_produce')
                    ->label('Produce')
                    ->boolean(),
                Tables\Columns\TextColumn::make('lifespan')
                    ->label('Lifespan')
                    ->state(fn (Ingredient $record) => $record->lifespan ? 'Configured' : 'Not set')
                    ->badge()
                    ->color(fn (Ingredient $record) => $record->lifespan ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_produce')
                    ->label('Is produce'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data): Model {
                        $response = app(VerdanttApiClient::class)->post('/admin/ingredients', [
                            'name' => $data['name'],
                            'category' => $data['category'] ?? null,
                            'image_url' => $data['image_url'] ?? null,
                            'is_produce' => (bool) ($data['is_produce'] ?? false),
                        ]);

                        if (! $response->successful()) {
                            Notification::make()
                                ->title('Failed to create ingredient')
                                ->body($response->json('message') ?? 'The API request failed.')
                                ->danger()
                                ->send();

                            throw new \Filament\Support\Exceptions\Halt();
                        }

                        return Ingredient::fromApi($response->json('data') ?? $data);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('lifespan')
                    ->label('Lifespan')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->form([
                        Forms\Components\TextInput::make('unripe_days')->numeric()->label('Unripe days'),
                        Forms\Components\TextInput::make('ripe_days')->numeric()->label('Ripe days'),
                        Forms\Components\TextInput::make('overripe_days')->numeric()->label('Overripe days'),
                        Forms\Components\TextInput::make('spoiled_days')->numeric()->label('Spoiled days'),
                        Forms\Components\TextInput::make('ideal_storage')->label('Ideal storage'),
                        Forms\Components\TextInput::make('image_url')->label('Lifespan image URL')->url(),
                    ])
                    ->fillForm(fn (Ingredient $record): array => $record->lifespan ?? [])
                    ->action(function (Ingredient $record, array $data) {
                        $response = app(VerdanttApiClient::class)->put(
                            "/admin/ingredients/{$record->id}/lifespan",
                            [
                                'unripe_days' => filled($data['unripe_days'] ?? null) ? (int) $data['unripe_days'] : null,
                                'ripe_days' => filled($data['ripe_days'] ?? null) ? (int) $data['ripe_days'] : null,
                                'overripe_days' => filled($data['overripe_days'] ?? null) ? (int) $data['overripe_days'] : null,
                                'spoiled_days' => filled($data['spoiled_days'] ?? null) ? (int) $data['spoiled_days'] : null,
                                'ideal_storage' => $data['ideal_storage'] ?? null,
                                'image_url' => $data['image_url'] ?? null,
                            ]
                        );

                        if (! $response->successful()) {
                            Notification::make()
                                ->title('Failed to update lifespan')
                                ->body($response->json('message') ?? 'The API request failed.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Lifespan updated')->success()->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        $response = app(VerdanttApiClient::class)->put("/admin/ingredients/{$record->id}", [
                            'name' => $data['name'],
                            'category' => $data['category'] ?? null,
                            'image_url' => $data['image_url'] ?? null,
                            'is_produce' => (bool) ($data['is_produce'] ?? false),
                        ]);

                        if (! $response->successful()) {
                            Notification::make()
                                ->title('Failed to update ingredient')
                                ->body($response->json('message') ?? 'The API request failed.')
                                ->danger()
                                ->send();

                            throw new \Filament\Support\Exceptions\Halt();
                        }

                        return Ingredient::fromApi($response->json('data') ?? array_merge($data, ['id' => $record->id]));
                    }),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Model $record) {
                        $response = app(VerdanttApiClient::class)->delete("/admin/ingredients/{$record->id}");

                        if (! $response->successful()) {
                            Notification::make()
                                ->title('Failed to delete ingredient')
                                ->body($response->json('message') ?? 'The API request failed.')
                                ->danger()
                                ->send();

                            throw new \Filament\Support\Exceptions\Halt();
                        }
                    }),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIngredients::route('/'),
        ];
    }
}
