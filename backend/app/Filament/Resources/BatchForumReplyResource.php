<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchForumReplyResource\Pages;
use App\Filament\Resources\BatchForumReplyResource\RelationManagers;
use App\Models\BatchForumReply;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchForumReplyResource extends Resource
{
    protected static ?string $model = BatchForumReply::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = 'Forum';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->schema([

                    Section::make()->schema([
                        Forms\Components\Select::make('topic_id')
                            ->relationship('topic', 'title') // Add topic() relation in model
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'title') // Add batch() relation
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('parent_reply_id')
                            ->relationship('parentReply', 'id') // Add parentReply() relation
                            ->nullable()
                            ->searchable(),

                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                    ])->columnSpan(2),

                    Section::make()->schema([
                        Forms\Components\Toggle::make('is_solution'),

                        Forms\Components\TextInput::make('like_count')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Textarea::make('attachment_urls')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_instructor_reply'),

                        Forms\Components\Select::make('created_by')
                            ->relationship('creator', 'name') // Add creator() relation
                            ->required()
                            ->searchable(),
                    ])->columnSpan(1),

                ])->columns(3)->extraAttributes(['class' => 'items-stretch']),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reply_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('topic_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('batch_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent_reply_id')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_solution')
                    ->boolean(),
                Tables\Columns\TextColumn::make('like_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_instructor_reply')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
        return 'Forum Reply'; // Sidebar label
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
            'index' => Pages\ListBatchForumReplies::route('/'),
            'create' => Pages\CreateBatchForumReply::route('/create'),
            'edit' => Pages\EditBatchForumReply::route('/{record}/edit'),
        ];
    }
}
