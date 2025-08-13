<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchQuestionResource\Pages;
use App\Filament\Resources\BatchQuestionResource\RelationManagers;
use App\Models\BatchQuestion;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchQuestionResource extends Resource
{
    protected static ?string $model = BatchQuestion::class;
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Question';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->schema([

                    // Left side: Main Question Content
                    Section::make()->schema([
                        Forms\Components\Select::make('assessment_id')
                            ->relationship('assessment', 'title') // Add assessment() relation in model
                            ->required()
                            ->searchable(),

                        Forms\Components\Textarea::make('question_text')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('explanation')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('media_url')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columnSpan(2),

                    // Right side: Question Settings
                    Section::make()->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_required'),

                        Forms\Components\Select::make('difficulty_level')
                            ->options([
                                'easy'   => 'Easy',
                                'medium' => 'Medium',
                                'hard'   => 'Hard',
                            ])
                            ->searchable(),
                        Forms\Components\Select::make('question_type')
                            ->options([
                                'multiple_choice' => 'Multiple Choice',
                                'true_false'      => 'True / False',
                                'short_answer'    => 'Short Answer',
                                'fill_in_blank'   => 'Fill in the Blank',
                                'matching'        => 'Matching',
                            ])
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('points')
                            ->numeric()
                            ->default(1.00),

                        Forms\Components\Textarea::make('tags')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columnSpan(1),

                ])->columns(3)->extraAttributes(['class' => 'items-stretch']),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assessment_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_type'),
                Tables\Columns\TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_required')
                    ->boolean(),
                Tables\Columns\TextColumn::make('difficulty_level'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
        return 'Questions'; // Sidebar label
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
            'index' => Pages\ListBatchQuestions::route('/'),
            'create' => Pages\CreateBatchQuestion::route('/create'),
            'edit' => Pages\EditBatchQuestion::route('/{record}/edit'),
        ];
    }
}
