<?php

namespace App\Filament\Resources\BatchAssessmentResource\Pages;

use App\Filament\Resources\BatchAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchAssessment extends EditRecord
{
    protected static string $resource = BatchAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
