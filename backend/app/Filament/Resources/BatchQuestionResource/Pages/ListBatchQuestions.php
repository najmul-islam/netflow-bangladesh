<?php

namespace App\Filament\Resources\BatchQuestionResource\Pages;

use App\Filament\Resources\BatchQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchQuestions extends ListRecords
{
    protected static string $resource = BatchQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
