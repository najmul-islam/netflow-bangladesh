<?php

namespace App\Filament\User\Resources\BatchEnrollmentResource\Pages;

use App\Filament\User\Resources\BatchEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchEnrollments extends ListRecords
{
    protected static string $resource = BatchEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
