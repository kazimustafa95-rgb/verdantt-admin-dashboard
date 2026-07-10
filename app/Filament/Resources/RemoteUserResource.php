<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RemoteUserResource\Pages;
use App\Models\RemoteUser;
use App\Services\VerdanttApiClient;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RemoteUserResource extends Resource
{
    protected static ?string $model = RemoteUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'user';

    protected static ?string $navigationGroup = 'Community';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('role')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_deleted')
                    ->label('Deleted')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_deleted')
                    ->label('Account status')
                    ->trueLabel('Deleted')
                    ->falseLabel('Active'),
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                        'super_admin' => 'Super Admin',
                    ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRemoteUsers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
