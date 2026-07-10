<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactFormResource\Pages;
use App\Models\ContactForm;
use App\Services\VerdanttApiClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactFormResource extends Resource
{
    protected static ?string $model = ContactForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = collect(app(VerdanttApiClient::class)->get('/admin/contact-forms')->json('data') ?? [])
            ->where('is_read', false)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->disabled(),
                Forms\Components\TextInput::make('email')->disabled(),
                Forms\Components\TextInput::make('subject')->disabled(),
                Forms\Components\Textarea::make('message')->disabled()->rows(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Read status')
                    ->trueLabel('Read')
                    ->falseLabel('Unread'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('markAsRead')
                    ->label('Mark as read')
                    ->icon('heroicon-o-envelope-open')
                    ->color('gray')
                    ->visible(fn (ContactForm $record) => ! $record->is_read)
                    ->action(function (ContactForm $record) {
                        $response = app(VerdanttApiClient::class)->put("/admin/contact-forms/{$record->id}/read");

                        if (! $response->successful()) {
                            Notification::make()
                                ->title('Failed to mark as read')
                                ->body($response->json('message') ?? 'The API request failed.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Marked as read')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Model $record) {
                        $response = app(VerdanttApiClient::class)->delete("/admin/contact-forms/{$record->id}");

                        if (! $response->successful()) {
                            Notification::make()
                                ->title('Failed to delete submission')
                                ->body($response->json('message') ?? 'The API request failed.')
                                ->danger()
                                ->send();

                            throw new \Filament\Support\Exceptions\Halt();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageContactForms::route('/'),
        ];
    }
}
