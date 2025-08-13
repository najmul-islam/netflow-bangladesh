<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchForumResource\Pages;
use App\Filament\Resources\BatchForumResource\RelationManagers;
use App\Models\BatchForum;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchForumResource extends Resource
{
    protected static ?string $model = BatchForum::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Forum';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->schema([
                    Section::make()->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')->rows(15),
                    ])->columnSpan(2),

                    Section::make()->schema([
                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'title') // 'batch' = relation name in model, 'title' = display column
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('forum_type')
                            ->options([
                                'general'      => 'General',
                                'announcements' => 'Announcements',
                                'q_and_a'      => 'Q & A',
                                'assignments'  => 'Assignments',
                                'projects'     => 'Projects',
                                'social'       => 'Social',
                            ])
                            ->default('general')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_locked'),
                        Forms\Components\Toggle::make('is_announcement_only'),
                        Forms\Components\Toggle::make('auto_subscribe_students'),
                        Forms\Components\Select::make('created_by')
                            ->relationship('creator', 'name') // Add creator() in model
                            ->required()
                            ->searchable(),
                    ])->columnSpan(1)
                ])->columns(3)->extraAttributes(['class' => 'items-stretch'])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('forum_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('batch_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('forum_type'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_announcement_only')
                    ->boolean(),
                Tables\Columns\IconColumn::make('auto_subscribe_students')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable(),
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
        return 'Forums'; // Sidebar label
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
            'index' => Pages\ListBatchForums::route('/'),
            'create' => Pages\CreateBatchForum::route('/create'),
            'edit' => Pages\EditBatchForum::route('/{record}/edit'),
        ];
    }
}
