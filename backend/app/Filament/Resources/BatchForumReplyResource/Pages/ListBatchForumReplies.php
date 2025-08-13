<?php

namespace App\Filament\Resources\BatchForumReplyResource\Pages;

use App\Filament\Resources\BatchForumReplyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchForumReplies extends ListRecords
{
    protected static string $resource = BatchForumReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
