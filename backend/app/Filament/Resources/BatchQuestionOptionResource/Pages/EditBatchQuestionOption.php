<?php

namespace App\Filament\Resources\BatchQuestionOptionResource\Pages;

use App\Filament\Resources\BatchQuestionOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchQuestionOption extends EditRecord
{
    protected static string $resource = BatchQuestionOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
