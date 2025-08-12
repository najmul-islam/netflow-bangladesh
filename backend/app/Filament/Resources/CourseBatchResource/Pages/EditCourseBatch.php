<?php

namespace App\Filament\Resources\CourseBatchResource\Pages;

use App\Filament\Resources\CourseBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseBatch extends EditRecord
{
    protected static string $resource = CourseBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
