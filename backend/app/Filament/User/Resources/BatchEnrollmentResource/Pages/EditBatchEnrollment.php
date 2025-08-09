<?php

namespace App\Filament\User\Resources\BatchEnrollmentResource\Pages;

use App\Filament\User\Resources\BatchEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchEnrollment extends EditRecord
{
    protected static string $resource = BatchEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
