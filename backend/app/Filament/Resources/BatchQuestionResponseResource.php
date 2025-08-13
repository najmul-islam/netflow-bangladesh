<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchQuestionResponseResource\Pages;
use App\Filament\Resources\BatchQuestionResponseResource\RelationManagers;
use App\Models\BatchQuestionResponse;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchQuestionResponseResource extends Resource
{
    protected static ?string $model = BatchQuestionResponse::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Question';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->schema([

                    // Left: Response Content
                    Section::make()->schema([
                        Forms\Components\Select::make('attempt_id')
                            ->relationship('attempt', 'id') // Add attempt() relation in model
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('question_id')
                            ->relationship('question', 'question_text') // Add question() relation
                            ->required()
                            ->searchable(),

                        Forms\Components\Textarea::make('selected_options')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('text_response')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('file_uploads')
                            ->multiple()                // allow multiple files
                            ->disk('public')            // choose the storage disk (config/filesystems.php)
                            ->directory('responses')    // optional subfolder
                            ->enableDownload()          // allow downloading uploaded files
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('feedback')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columnSpan(2),

                    // Right: Response Settings
                    Section::make()->schema([
                        Forms\Components\TextInput::make('points_earned')
                            ->numeric()
                            ->default(0.00),

                        Forms\Components\Toggle::make('is_correct'),

                        Forms\Components\TextInput::make('time_spent_seconds')
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
                Tables\Columns\TextColumn::make('response_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('attempt_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('points_earned')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_correct')
                    ->boolean(),
                Tables\Columns\TextColumn::make('time_spent_seconds')
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
        return 'Question Response';
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
            'index' => Pages\ListBatchQuestionResponses::route('/'),
            'create' => Pages\CreateBatchQuestionResponse::route('/create'),
            'edit' => Pages\EditBatchQuestionResponse::route('/{record}/edit'),
        ];
    }
}
