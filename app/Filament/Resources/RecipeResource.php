<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecipeResource\Pages;
use App\Models\Recipe;
use App\Services\VerdanttApiClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    /**
     * @return \Illuminate\Support\Collection<int, array>
     */
    public static function allIngredients(): \Illuminate\Support\Collection
    {
        return collect(app(VerdanttApiClient::class)->get('/admin/ingredients')->json('data') ?? []);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recipe details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3),
                        Forms\Components\Textarea::make('instructions')
                            ->columnSpanFull()
                            ->rows(6),
                        Forms\Components\TextInput::make('prep_time')->numeric(),
                        Forms\Components\TextInput::make('prep_unit')->default('minutes'),
                        Forms\Components\TextInput::make('cook_time')->numeric(),
                        Forms\Components\TextInput::make('cook_unit')->default('minutes'),
                        Forms\Components\TextInput::make('servings')->numeric(),
                        Forms\Components\TextInput::make('appliance'),
                        Forms\Components\TextInput::make('appliance_substitute'),
                        Forms\Components\TextInput::make('cookbook_title'),
                        Forms\Components\TextInput::make('dietary_restrictions')
                            ->helperText('Comma-separated, e.g. Vegetarian, Gluten-Free'),
                        Forms\Components\TextInput::make('ingredient_allergens')
                            ->helperText('Comma-separated, e.g. Milk, Peanuts'),
                        Forms\Components\TextInput::make('keywords')
                            ->helperText('Comma-separated'),
                    ]),

                Forms\Components\Section::make('Image')
                    ->schema([
                        Forms\Components\Placeholder::make('current_image')
                            ->label('Current image')
                            ->content(fn (?Recipe $record) => $record?->image_url
                                ? new \Illuminate\Support\HtmlString('<img src="' . e($record->image_url) . '" class="h-24 rounded-lg" />')
                                : 'No image')
                            ->visible(fn (?Recipe $record) => $record !== null),
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->storeFiles(false)
                            ->helperText('Upload to replace the current image. Leave empty to keep it.'),
                    ]),

                Forms\Components\Section::make('Ingredients')
                    ->schema([
                        Forms\Components\Repeater::make('ingredients')
                            ->schema([
                                Forms\Components\Select::make('ingredient_id')
                                    ->label('Ingredient')
                                    ->options(fn () => static::allIngredients()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')->numeric(),
                                Forms\Components\TextInput::make('quantity_label'),
                                Forms\Components\TextInput::make('unit'),
                                Forms\Components\TextInput::make('prefix'),
                                Forms\Components\TextInput::make('notes'),
                                Forms\Components\Toggle::make('is_optional'),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => static::allIngredients()
                                ->firstWhere('id', $state['ingredient_id'] ?? null)['name'] ?? null)
                            ->addActionLabel('Add ingredient')
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label(''),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('cookbook_title')
                    ->label('Cookbook')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('servings')
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Reviews')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Model $record) {
                        $response = app(VerdanttApiClient::class)->delete("/admin/recipes/{$record->id}");

                        if (! $response->successful()) {
                            Notification::make()
                                ->title('Failed to delete recipe')
                                ->body($response->json('message') ?? 'The API request failed.')
                                ->danger()
                                ->send();

                            throw new \Filament\Support\Exceptions\Halt();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }

    /**
     * Build the plain (non-file) multipart fields shared by create and update requests.
     */
    public static function apiFields(array $data): array
    {
        $ingredients = collect($data['ingredients'] ?? [])
            ->map(fn (array $row) => [
                'ingredient_id' => (int) $row['ingredient_id'],
                'quantity' => filled($row['quantity'] ?? null) ? (float) $row['quantity'] : null,
                'quantity_label' => $row['quantity_label'] ?? null,
                'unit' => $row['unit'] ?? null,
                'prefix' => $row['prefix'] ?? null,
                'notes' => $row['notes'] ?? null,
                'is_optional' => (bool) ($row['is_optional'] ?? false),
            ])
            ->values()
            ->all();

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'instructions' => $data['instructions'] ?? '',
            'prep_time' => $data['prep_time'] ?? '',
            'prep_unit' => $data['prep_unit'] ?? '',
            'cook_time' => $data['cook_time'] ?? '',
            'cook_unit' => $data['cook_unit'] ?? '',
            'servings' => $data['servings'] ?? '',
            'appliance' => $data['appliance'] ?? '',
            'appliance_substitute' => $data['appliance_substitute'] ?? '',
            'dietary_restrictions' => $data['dietary_restrictions'] ?? '',
            'ingredient_allergens' => $data['ingredient_allergens'] ?? '',
            'cookbook_title' => $data['cookbook_title'] ?? '',
            'keywords' => $data['keywords'] ?? '',
            'ingredients' => json_encode($ingredients),
        ];
    }
}
