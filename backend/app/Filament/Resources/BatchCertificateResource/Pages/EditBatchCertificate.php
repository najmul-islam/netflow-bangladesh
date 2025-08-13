<?php

namespace App\Filament\Resources\BatchCertificateResource\Pages;

use App\Filament\Resources\BatchCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchCertificate extends EditRecord
{
    protected static string $resource = BatchCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
