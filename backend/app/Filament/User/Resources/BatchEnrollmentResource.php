<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\BatchEnrollmentResource\Pages;
use App\Filament\User\Resources\BatchEnrollmentResource\RelationManagers;
use App\Models\BatchEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchEnrollmentResource extends Resource
{
    protected static ?string $model = BatchEnrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchEnrollments::route('/'),
            'create' => Pages\CreateBatchEnrollment::route('/create'),
            'edit' => Pages\EditBatchEnrollment::route('/{record}/edit'),
        ];
    }
}
