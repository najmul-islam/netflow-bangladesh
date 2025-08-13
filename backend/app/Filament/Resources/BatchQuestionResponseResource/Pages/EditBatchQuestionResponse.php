<?php

namespace App\Filament\Resources\BatchQuestionResponseResource\Pages;

use App\Filament\Resources\BatchQuestionResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchQuestionResponse extends EditRecord
{
    protected static string $resource = BatchQuestionResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
