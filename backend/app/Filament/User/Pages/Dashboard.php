<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\User\Widgets\StatsOverview;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.user.pages.dashboard';
    
    protected static ?string $title = 'Dashboard';
    
    public function getColumns(): int | string | array
    {
        return 2;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }
}
