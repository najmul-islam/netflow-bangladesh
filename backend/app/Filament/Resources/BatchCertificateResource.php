<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchCertificateResource\Pages;
use App\Filament\Resources\BatchCertificateResource\RelationManagers;
use App\Models\BatchCertificate;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchCertificateResource extends Resource
{
    protected static ?string $model = BatchCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Course Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->schema([

                    // Left: Certificate Info
                    Section::make()->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name') // Add user() relation in model
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'title') // Add batch() relation
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('template_id')
                            ->relationship('template', 'name') // Add template() relation
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('certificate_number')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\DateTimePicker::make('issued_date')
                            ->required(),

                        Forms\Components\DateTimePicker::make('expiry_date'),

                        Forms\Components\TextInput::make('verification_code')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('certificate_url')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('metadata')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columnSpan(2),

                    // Right: Revocation & Status
                    Section::make()->schema([
                        Forms\Components\Toggle::make('is_revoked'),

                        Forms\Components\DateTimePicker::make('revoked_at'),

                        Forms\Components\Select::make('revoked_by')
                            ->relationship('revoker', 'name') // Add revoker() relation
                            ->nullable()
                            ->searchable(),

                        Forms\Components\Textarea::make('revocation_reason')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('batch_completion_date'),

                        Forms\Components\TextInput::make('final_grade')
                            ->numeric()
                            ->default(null),

                        Forms\Components\TextInput::make('attendance_percentage')
                            ->numeric()
                            ->default(null),

                        Forms\Components\TextInput::make('class_rank')
                            ->numeric()
                            ->default(null),

                        Forms\Components\TextInput::make('total_students')
                            ->numeric()
                            ->default(null),

                        Forms\Components\Textarea::make('special_achievements')
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
                Tables\Columns\TextColumn::make('certificate_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('batch_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('template_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('certificate_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issued_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verification_code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_revoked')
                    ->boolean(),
                Tables\Columns\TextColumn::make('revoked_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('revoked_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('batch_completion_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_grade')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class_rank')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_students')
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchCertificates::route('/'),
            'create' => Pages\CreateBatchCertificate::route('/create'),
            'edit' => Pages\EditBatchCertificate::route('/{record}/edit'),
        ];
    }
}
