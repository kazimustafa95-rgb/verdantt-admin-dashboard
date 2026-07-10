<?php

namespace App\Filament\Pages;

use App\Services\VerdanttApiClient;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BroadcastNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Broadcast Notification';

    protected static ?string $title = 'Broadcast Notification';

    protected static string $view = 'filament.pages.broadcast-notifications';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'audience' => 'all',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('audience')
                    ->label('Send to')
                    ->options([
                        'all' => 'All users',
                        'paid' => 'Premium (paid) users',
                        'unpaid' => 'Free users',
                    ])
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('message')
                    ->required()
                    ->rows(4),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();

        $endpoint = match ($state['audience']) {
            'paid' => '/admin/notifications/broadcast/paid',
            'unpaid' => '/admin/notifications/broadcast/unpaid',
            default => '/admin/notifications/broadcast',
        };

        $response = app(VerdanttApiClient::class)->post($endpoint, [
            'title' => $state['title'],
            'message' => $state['message'],
        ]);

        if ($response->successful()) {
            Notification::make()
                ->title('Notification broadcast sent')
                ->success()
                ->send();

            $this->form->fill([
                'audience' => $state['audience'],
                'title' => '',
                'message' => '',
            ]);

            return;
        }

        Notification::make()
            ->title('Failed to send notification')
            ->body($response->json('message') ?? 'The API request failed.')
            ->danger()
            ->send();
    }
}
