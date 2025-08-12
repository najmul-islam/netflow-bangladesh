<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Profile Information')
                            ->description('Basic user profile information')
                            ->schema([
                                Forms\Components\FileUpload::make('avatar_url')
                                    ->label('Profile Picture')
                                    ->image()
                                    ->disk('public')
                                    ->directory('avatars')
                                    ->visibility('public')
                                    ->imagePreviewHeight('120')
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->columnSpanFull(),
                                    
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('first_name')
                                            ->label('First Name')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('Enter first name'),
                                            
                                        Forms\Components\TextInput::make('last_name')
                                            ->label('Last Name')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('Enter last name'),
                                    ]),
                                    
                                Forms\Components\TextInput::make('username')
                                    ->label('Username')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(100)
                                    ->placeholder('Enter unique username')
                                    ->alphaDash(),
                                    
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('Enter email address'),
                                    
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('+880 1XXX-XXXXXX'),
                                    
                                Forms\Components\Textarea::make('bio')
                                    ->label('Biography')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->placeholder('Tell us about yourself...')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),
                    ])
                    ->columnSpan(['lg' => 2]),
                    
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Security & Access')
                            ->description('Account security and access control')
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                                    ->required(fn (string $context) => $context === 'create')
                                    ->placeholder('Enter secure password')
                                    ->autocomplete('new-password')
                                    ->helperText('Leave blank to keep current password'),
                                    
                                Forms\Components\Select::make('status')
                                    ->label('Account Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive', 
                                        'banned' => 'Banned',
                                    ])
                                    ->required()
                                    ->default('active')
                                    ->native(false),
                                    
                                Forms\Components\Toggle::make('email_verified')
                                    ->label('Email Verified')
                                    ->helperText('Mark as verified if email is confirmed'),
                            ]),
                            
                        Forms\Components\Section::make('Additional Information')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Created')
                                    ->content(fn (?User $record): string => $record?->created_at?->diffForHumans() ?? '-'),
                                    
                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Last Modified')
                                    ->content(fn (?User $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                                    
                                Forms\Components\Placeholder::make('last_login')
                                    ->label('Last Login')
                                    ->content(fn (?User $record): string => $record?->last_login?->diffForHumans() ?? 'Never'),
                            ])
                            ->hidden(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=User&background=0B2E58&color=fff'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Username copied!')
                    ->fontFamily('mono'),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-m-envelope'),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-phone'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'banned' => 'danger',
                    }),
                    
                Tables\Columns\IconColumn::make('email_verified')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                    
                Tables\Columns\TextColumn::make('last_login')
                    ->label('Last Login')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Never'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'banned' => 'Banned',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('email_verified')
                    ->label('Email Verified')
                    ->placeholder('All users')
                    ->trueLabel('Verified users')
                    ->falseLabel('Unverified users'),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}