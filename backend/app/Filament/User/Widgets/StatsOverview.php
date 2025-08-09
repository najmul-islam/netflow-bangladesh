<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        return [
            Stat::make('Active Enrollments', $user->enrollments()->where('status', 'active')->count())
                ->description('Currently enrolled courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
                
            Stat::make('Completed Courses', $user->enrollments()->where('status', 'completed')->count())
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Certificates Earned', $user->certificates()->count())
                ->description('Total certificates')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),
        ];
    }
}
