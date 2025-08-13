<?php

namespace App\Filament\Resources\BatchForumResource\Pages;

use App\Filament\Resources\BatchForumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchForums extends ListRecords
{
    protected static string $resource = BatchForumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
