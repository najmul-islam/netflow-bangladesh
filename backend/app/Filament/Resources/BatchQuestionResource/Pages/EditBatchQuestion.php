<?php

namespace App\Filament\Resources\BatchQuestionResource\Pages;

use App\Filament\Resources\BatchQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchQuestion extends EditRecord
{
    protected static string $resource = BatchQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
