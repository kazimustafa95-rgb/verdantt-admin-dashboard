<?php

namespace App\Filament\Pages;

use App\Services\VerdanttApiClient;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageCmsPages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Terms & Privacy';

    protected static ?string $title = 'Terms & Privacy';

    protected static string $view = 'filament.pages.manage-cms-pages';

    public ?array $data = [];

    public function mount(): void
    {
        $client = app(VerdanttApiClient::class);

        $terms = $client->get('/admin/cms/terms')->json('data') ?? [];
        $privacy = $client->get('/admin/cms/privacy')->json('data') ?? [];

        $this->form->fill([
            'terms_title' => $terms['title'] ?? 'Terms of Use',
            'terms_content' => $terms['content'] ?? '',
            'terms_status' => $terms['status'] ?? 'published',
            'privacy_title' => $privacy['title'] ?? 'Privacy Policy',
            'privacy_content' => $privacy['content'] ?? '',
            'privacy_status' => $privacy['status'] ?? 'published',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Cms')
                    ->tabs([
                        Tabs\Tab::make('Terms of Use')
                            ->schema([
                                TextInput::make('terms_title')->label('Title')->required(),
                                Select::make('terms_status')
                                    ->label('Status')
                                    ->options(['published' => 'Published', 'draft' => 'Draft'])
                                    ->required(),
                                RichEditor::make('terms_content')
                                    ->label('Content')
                                    ->columnSpanFull(),
                            ]),
                        Tabs\Tab::make('Privacy Policy')
                            ->schema([
                                TextInput::make('privacy_title')->label('Title')->required(),
                                Select::make('privacy_status')
                                    ->label('Status')
                                    ->options(['published' => 'Published', 'draft' => 'Draft'])
                                    ->required(),
                                RichEditor::make('privacy_content')
                                    ->label('Content')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function saveTerms(): void
    {
        $state = $this->form->getState();

        $response = app(VerdanttApiClient::class)->put('/admin/cms/terms', [
            'title' => $state['terms_title'],
            'content' => $state['terms_content'],
            'status' => $state['terms_status'],
        ]);

        $this->notifyFromResponse($response, 'Terms of Use');
    }

    public function savePrivacy(): void
    {
        $state = $this->form->getState();

        $response = app(VerdanttApiClient::class)->put('/admin/cms/privacy', [
            'title' => $state['privacy_title'],
            'content' => $state['privacy_content'],
            'status' => $state['privacy_status'],
        ]);

        $this->notifyFromResponse($response, 'Privacy Policy');
    }

    protected function notifyFromResponse($response, string $label): void
    {
        if ($response->successful()) {
            Notification::make()
                ->title("{$label} saved")
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title("Failed to save {$label}")
            ->body($response->json('message') ?? 'The API request failed.')
            ->danger()
            ->send();
    }
}
