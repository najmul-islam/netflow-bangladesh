<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchQuestionOptionResource\Pages;
use App\Filament\Resources\BatchQuestionOptionResource\RelationManagers;
use App\Models\BatchQuestionOption;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchQuestionOptionResource extends Resource
{
    protected static ?string $model = BatchQuestionOption::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Question';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->schema([
                    // Left: Option Content
                    Section::make()->schema([
                        Forms\Components\Textarea::make('option_text')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('explanation')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columnSpan(2),

                    // Right: Option Settings
                    Section::make()->schema([
                        Forms\Components\Select::make('question_id')
                            ->relationship('question', 'question_text') // Add question() relation in model
                            ->required()
                            ->searchable(),
                        Forms\Components\Toggle::make('is_correct'),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])->columnSpan(1),

                ])->columns(3)->extraAttributes(['class' => 'items-stretch']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('option_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_id')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_correct')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
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
    public static function getNavigationLabel(): string
    {
        return 'Question Options'; // Sidebar label
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
            'index' => Pages\ListBatchQuestionOptions::route('/'),
            'create' => Pages\CreateBatchQuestionOption::route('/create'),
            'edit' => Pages\EditBatchQuestionOption::route('/{record}/edit'),
        ];
    }
}
