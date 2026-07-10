<?php

namespace App\Filament\Widgets;

use App\Services\VerdanttApiClient;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $data = app(VerdanttApiClient::class)->get('/admin/dashboard')->json('data') ?? [];

        $users = $data['users'] ?? [];

        return [
            Stat::make('Total Users', $users['total'] ?? 0)
                ->description(($users['active'] ?? 0) . ' active, ' . ($users['deleted'] ?? 0) . ' deleted')
                ->color('success'),

            Stat::make('Recipes', $data['recipes'] ?? 0)
                ->color('primary'),

            Stat::make('Ingredients', $data['ingredients'] ?? 0)
                ->color('primary'),

            Stat::make('Pantry Items', $data['pantry_items'] ?? 0)
                ->color('warning'),

            Stat::make('Grocery Items', $data['grocery_items'] ?? 0)
                ->color('warning'),

            Stat::make('Active Subscriptions', $data['subscriptions']['active'] ?? 0)
                ->color('success'),

            Stat::make('Notifications Sent', $data['notifications'] ?? 0)
                ->color('gray'),
        ];
    }
}
