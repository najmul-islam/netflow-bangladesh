<?php

namespace App\Filament\Resources\BatchQuestionOptionResource\Pages;

use App\Filament\Resources\BatchQuestionOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchQuestionOptions extends ListRecords
{
    protected static string $resource = BatchQuestionOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
