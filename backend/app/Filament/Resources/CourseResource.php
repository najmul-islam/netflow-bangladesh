<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use App\Models\CertificateTemplate;
use App\Models\CourseInstructor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Actions\Action;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Course Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Basic Information')
                            ->description('Essential course details')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Course Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Str::slug($state)))
                                    ->placeholder('Enter course title'),

                                Forms\Components\TextInput::make('slug')
                                    ->label('URL Slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->alphaDash()
                                    ->placeholder('auto-generated-from-title'),

                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('description')
                                            ->maxLength(500),
                                    ])
                                    ->placeholder('Select or create category'),

                                Forms\Components\Select::make('difficulty_level')
                                    ->label('Difficulty Level')
                                    ->options([
                                        'beginner' => 'Beginner',
                                        'intermediate' => 'Intermediate',
                                        'advanced' => 'Advanced',
                                        'expert' => 'Expert',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('language')
                                    ->label('Course Language')
                                    ->options([
                                        'en' => 'English',
                                        'bn' => 'Bengali',
                                        'hi' => 'Hindi',
                                        'ar' => 'Arabic',
                                    ])
                                    ->default('en')
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('estimated_duration_hours')
                                    ->label('Duration (Hours)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(1000)
                                    ->suffix('hours')
                                    ->placeholder('e.g. 40'),
                            ]),

                        Forms\Components\Section::make('Content & Media')
                            ->description('Course descriptions and media')
                            ->schema([
                                Forms\Components\Textarea::make('short_description')
                                    ->label('Short Description')
                                    ->required()
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->placeholder('Brief course summary for listings')
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->label('Full Description')
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'bulletList',
                                        'orderedList',
                                        'h2',
                                        'h3',
                                        'link',
                                        'undo',
                                        'redo',
                                    ])
                                    ->placeholder('Detailed course description with objectives, outcomes, etc.')
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('thumbnail_url')
                                    ->label('Course Thumbnail')
                                    ->image()
                                    ->disk('public')
                                    ->directory('courses/thumbnails')
                                    ->imagePreviewHeight('200')
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('16:9')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Recommended: 1920x1080px, Max: 5MB'),

                                Forms\Components\TextInput::make('trailer_video_url')
                                    ->label('Trailer Video URL')
                                    ->url()
                                    ->placeholder('https://youtube.com/watch?v=...')
                                    ->helperText('YouTube, Vimeo, or direct video URL'),
                            ]),

                        Forms\Components\Section::make('Learning Details')
                            ->description('Prerequisites and learning objectives')
                            ->schema([
                                Forms\Components\TagsInput::make('prerequisites')
                                    ->label('Prerequisites')
                                    ->placeholder('Add prerequisites (press Enter after each)')
                                    ->helperText('What students need to know before taking this course'),

                                Forms\Components\TagsInput::make('learning_objectives')
                                    ->label('Learning Objectives')
                                    ->placeholder('Add learning objectives (press Enter after each)')
                                    ->helperText('What students will learn by the end of this course'),
                            ]),

                        Forms\Components\Section::make('Course Instructors')
                            ->description('Assign instructors to this course')
                            ->schema([
                                Forms\Components\Select::make('instructor_ids')
                                    ->label('Instructors')
                                    ->multiple()
                                    ->relationship('instructors', 'first_name')
                                    ->options(function () {
                                        try {
                                            // Direct query without relationships to avoid SQL errors
                                            return \DB::table('users')
                                                ->join('user_roles', 'users.user_id', '=', 'user_roles.user_id')
                                                ->join('roles', 'user_roles.role_id', '=', 'roles.role_id')
                                                ->whereIn('roles.role_name', ['instructor', 'admin', 'super_admin'])
                                                ->select('users.user_id', 'users.first_name', 'users.last_name', 'users.email')
                                                ->get()
                                                ->mapWithKeys(function ($user) {
                                                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                    if (empty($fullName)) {
                                                        $fullName = $user->email ?? 'Unknown';
                                                    }
                                                    return [$user->user_id => $fullName . ' (' . $user->email . ')'];
                                                });
                                        } catch (\Exception $e) {
                                            // Fallback - get all users
                                            return User::all()->mapWithKeys(function ($user) {
                                                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                if (empty($fullName)) {
                                                    $fullName = $user->email ?? 'Unknown';
                                                }
                                                return [$user->user_id => $fullName . ' (' . $user->email . ')'];
                                            });
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select instructors')
                                    ->helperText('Select one or more instructors for this course')
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // Update instructor summary
                                        if (is_array($state) && !empty($state)) {
                                            $users = User::whereIn('user_id', $state)->get();
                                            $summary = "**Selected Instructors: " . count($users) . "**\n\n";
                                            foreach ($users as $user) {
                                                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                if (empty($fullName)) {
                                                    $fullName = $user->email ?? 'Unknown';
                                                }
                                                $summary .= "👨‍🏫 {$fullName}\n";
                                            }
                                            $set('instructor_summary', $summary);
                                        } else {
                                            $set('instructor_summary', '**No instructors selected**');
                                        }
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pricing & Enrollment')
                            ->schema([
                                Forms\Components\Toggle::make('is_free')
                                    ->label('Free Course')
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, $state) =>
                                        $state ? $set('price', 0) : null),

                                Forms\Components\TextInput::make('price')
                                    ->label('Course Price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->hidden(fn (Forms\Get $get) => $get('is_free')),

                                Forms\Components\Select::make('currency')
                                    ->label('Currency')
                                    ->options([
                                        'USD' => 'USD ($)',
                                        'BDT' => 'BDT (৳)',
                                        'EUR' => 'EUR (€)',
                                        'GBP' => 'GBP (£)',
                                    ])
                                    ->default('USD')
                                    ->hidden(fn (Forms\Get $get) => $get('is_free')),

                                Forms\Components\TextInput::make('max_enrollments')
                                    ->label('Max Enrollments')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Leave empty for unlimited')
                                    ->helperText('Total enrollment limit'),
                            ]),

                        Forms\Components\Section::make('Publication')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Course Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'published' => 'Published',
                                        'archived' => 'Archived',
                                        'private' => 'Private',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Toggle::make('featured')
                                    ->label('Featured Course')
                                    ->helperText('Show on homepage'),

                                Forms\Components\DateTimePicker::make('published_at')
                                    ->label('Publish Date')
                                    ->native(false),

                                Forms\Components\Select::make('created_by')
                                    ->label('Created By')
                                    ->relationship('creator', 'email')
                                    ->default(auth()->id())
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Forms\Components\Section::make('Instructor Summary')
                            ->schema([
                                Forms\Components\Placeholder::make('instructor_summary')
                                    ->label('')
                                    ->content(function (Forms\Get $get) {
                                        $instructorIds = $get('instructor_ids') ?? [];
                                        
                                        if (empty($instructorIds)) {
                                            return '**No instructors selected**';
                                        }

                                        try {
                                            $users = User::whereIn('user_id', $instructorIds)->get();
                                            $summary = "**Selected Instructors: " . count($users) . "**\n\n";
                                            
                                            foreach ($users as $user) {
                                                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                if (empty($fullName)) {
                                                    $fullName = $user->email ?? 'Unknown';
                                                }
                                                $summary .= "👨‍🏫 {$fullName}\n";
                                            }
                                            
                                            return $summary;
                                        } catch (\Exception $e) {
                                            return '**Error loading instructors**';
                                        }
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (Forms\Get $get) => !empty($get('instructor_ids'))),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl('https://via.placeholder.com/100x100/0B2E58/ffffff?text=Course'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Course Title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(50),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('instructors')
                    ->label('Instructors')
                    ->badge()
                    ->color('info')
                    ->limit(30)
                    ->placeholder('No instructors')
                    ->formatStateUsing(function ($record) {
                        try {
                            $instructors = \DB::table('course_instructors')
                                ->join('users', 'course_instructors.user_id', '=', 'users.user_id')
                                ->where('course_instructors.course_id', $record->course_id)
                                ->select('users.first_name', 'users.last_name', 'users.email')
                                ->get();

                            if ($instructors->isEmpty()) return 'No instructors';

                            return $instructors->map(function ($instructor) {
                                $fullName = trim(($instructor->first_name ?? '') . ' ' . ($instructor->last_name ?? ''));
                                return empty($fullName) ? $instructor->email : $fullName;
                            })->join(', ');
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    }),

                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                        'expert' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('estimated_duration_hours')
                    ->label('Duration')
                    ->suffix(' hrs')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->color(fn ($record) => $record->is_free ? 'success' : 'primary')
                    ->formatStateUsing(fn ($record) => $record->is_free ? 'FREE' : '$' . number_format($record->price, 2)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'warning',
                        'private' => 'info',
                        default => 'secondary',
                    }),

                Tables\Columns\IconColumn::make('featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->placeholder('Not published'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                        'expert' => 'Expert',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                        'private' => 'Private',
                    ]),

                Tables\Filters\SelectFilter::make('instructors')
                    ->label('Instructor')
                    ->options(function () {
                        try {
                            return \DB::table('users')
                                ->join('user_roles', 'users.user_id', '=', 'user_roles.user_id')
                                ->join('roles', 'user_roles.role_id', '=', 'roles.role_id')
                                ->whereIn('roles.role_name', ['instructor', 'admin', 'super_admin'])
                                ->select('users.user_id', 'users.first_name', 'users.last_name', 'users.email')
                                ->get()
                                ->mapWithKeys(function ($user) {
                                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                    if (empty($fullName)) {
                                        $fullName = $user->email ?? 'Unknown';
                                    }
                                    return [$user->user_id => $fullName];
                                });
                        } catch (\Exception $e) {
                            return User::all()->mapWithKeys(function ($user) {
                                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                if (empty($fullName)) {
                                    $fullName = $user->email ?? 'Unknown';
                                }
                                return [$user->user_id => $fullName];
                            });
                        }
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query) => $query->whereExists(function ($subQuery) use ($data) {
                                $subQuery->select(\DB::raw(1))
                                    ->from('course_instructors')
                                    ->whereColumn('course_instructors.course_id', 'courses.course_id')
                                    ->where('course_instructors.user_id', $data['value']);
                            })
                        );
                    }),

                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Free Course')
                    ->placeholder('All courses')
                    ->trueLabel('Free courses')
                    ->falseLabel('Paid courses'),

                Tables\Filters\TernaryFilter::make('featured')
                    ->label('Featured')
                    ->placeholder('All courses')
                    ->trueLabel('Featured courses')
                    ->falseLabel('Regular courses'),

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'published']))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('feature')
                        ->label('Feature Selected')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['featured' => true]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return static::getModel()::count();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}