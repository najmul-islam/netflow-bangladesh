<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Filament\Resources\LessonResource\RelationManagers;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\CourseBatch;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    
    protected static ?string $navigationGroup = 'Course Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Basic Information')
                            ->description('Essential lesson details')
                            ->schema([
                                Forms\Components\Select::make('course_id')
                                    ->label('Course')
                                    ->relationship('course', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->placeholder('Select course first')
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('module_id', null);
                                        $set('batch_id', null);
                                    }),
                                    
                                Forms\Components\Select::make('module_id')
                                    ->label('Module')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select module')
                                    ->options(function (Forms\Get $get) {
                                        $courseId = $get('course_id');
                                        if (!$courseId) return [];
                                        
                                        return Module::where('course_id', $courseId)
                                            ->orderBy('sort_order')
                                            ->pluck('title', 'module_id');
                                    })
                                    ->createOptionForm([
                                        Forms\Components\Select::make('course_id')
                                            ->label('Course')
                                            // ->relationship('course', 'title')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->default(fn (Forms\Get $get) => $get('../../course_id'))
                                            ->placeholder('Select course for this module')
                                             ->options(function () {
                                                return Course::pluck('title', 'course_id');
                                            }),
                                                                                
                                        Forms\Components\Section::make('Module Information')
                                            ->description('Create a new module for the selected course')
                                            ->schema([
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Module Title')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Enter module title')
                                                    ->columnSpanFull(),
                                                    
                                                Forms\Components\RichEditor::make('description')
                                                    ->label('Module Description')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'underline',
                                                        'bulletList',
                                                        'orderedList',
                                                        'h2',
                                                        'h3',
                                                        'link',
                                                    ])
                                                    ->placeholder('Describe what this module covers...')
                                                    ->columnSpanFull(),
                                            ]),
                                            
                                        Forms\Components\Section::make('Module Settings')
                                            ->description('Configure module behavior and availability')
                                            ->schema([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('sort_order')
                                                            ->label('Module Order')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->minValue(1)
                                                            ->placeholder('Module order in course'),
                                                            
                                                        Forms\Components\Toggle::make('is_published')
                                                            ->label('Publish Module')
                                                            ->default(true)
                                                            ->helperText('Make module visible to students'),
                                                    ]),
                                            ]),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        $module = Module::create($data);
                                        return $module->module_id;
                                    })
                                    ->createOptionModalHeading('Create New Module'),
                                    
                                Forms\Components\Select::make('batch_id')
                                    ->label('Batch (Optional)')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select batch or leave empty for all batches')
                                    ->options(function (Forms\Get $get) {
                                        $courseId = $get('course_id');
                                        if (!$courseId) return [];
                                        
                                        return CourseBatch::where('course_id', $courseId)
                                            ->orderBy('batch_name')
                                            ->pluck('batch_name', 'batch_id');
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        // Auto-set batch specific when batch is selected
                                        $set('is_batch_specific', !empty($state));
                                    }),
                                    
                                Forms\Components\TextInput::make('title')
                                    ->label('Lesson Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter lesson title'),
                                    
                                Forms\Components\Select::make('content_type')
                                    ->label('Content Type')
                                    ->options([
                                        'video' => '🎥 Video',
                                        'text' => '📝 Text/Article',
                                        'pdf' => '📄 PDF Document',
                                        'quiz' => '❓ Quiz/Assessment',
                                        'live_class' => '🔴 Live Class',
                                        'assignment' => '📋 Assignment',
                                        'resource' => '📎 Resource/Download',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->live(),
                                    
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('duration_minutes')
                                            ->label('Duration (Minutes)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->suffix('min')
                                            ->placeholder('e.g. 45'),
                                            
                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Lesson Order')
                                            ->numeric()
                                            ->default(function (Forms\Get $get) {
                                                $moduleId = $get('module_id');
                                                if (!$moduleId) return 1;
                                                
                                                return Lesson::where('module_id', $moduleId)->max('sort_order') + 1;
                                            })
                                            ->placeholder('Auto-calculated'),
                                    ]),
                            ]),
                            
                        Forms\Components\Section::make('Content')
                            ->description('Lesson content and materials')
                            ->schema([
                                // Content URL or File Upload
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('content_url')
                                            ->label('Content URL')
                                            ->url()
                                            ->placeholder('https://youtube.com/watch?v=... or file URL')
                                            ->helperText('Enter URL for video, PDF, or external resource'),
                                            
                                        Forms\Components\FileUpload::make('content_file')
                                            ->label('Upload File')
                                            ->directory('lesson-content')
                                            ->acceptedFileTypes(['video/*', 'application/pdf', 'image/*'])
                                            ->maxSize(100000) // 100MB
                                            ->helperText('Upload video, PDF, or image file'),
                                    ])
                                    ->visible(fn (Forms\Get $get) => in_array($get('content_type'), ['video', 'pdf', 'resource'])),
                                    
                                Forms\Components\RichEditor::make('content_text')
                                    ->label('Text Content')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'bulletList',
                                        'orderedList',
                                        'h2',
                                        'h3',
                                        'link',
                                        'codeBlock',
                                    ])
                                    ->placeholder('Write your lesson content here...')
                                    ->columnSpanFull()
                                    ->visible(fn (Forms\Get $get) => in_array($get('content_type'), ['text', 'assignment'])),
                                    
                                Forms\Components\Textarea::make('content_text')
                                    ->label('Quiz Instructions')
                                    ->rows(3)
                                    ->placeholder('Quiz instructions and description...')
                                    ->columnSpanFull()
                                    ->visible(fn (Forms\Get $get) => $get('content_type') === 'quiz'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),
                    
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Settings')
                            ->schema([
                                Forms\Components\Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(true)
                                    ->helperText('Make lesson visible to students'),
                                    
                                Forms\Components\Toggle::make('is_free_preview')
                                    ->label('Free Preview')
                                    ->helperText('Allow non-enrolled users to view'),
                                    
                                Forms\Components\Toggle::make('is_batch_specific')
                                    ->label('Batch Specific')
                                    ->helperText('Only for selected batch'),
                            ]),
                            
                        Forms\Components\Section::make('Module Info')
                            ->schema([
                                Forms\Components\Placeholder::make('module_info')
                                    ->label('')
                                    ->content(function (Forms\Get $get) {
                                        $moduleId = $get('module_id');
                                        if (!$moduleId) {
                                            return 'Select a module to see details';
                                        }
                                        
                                        $module = Module::find($moduleId);
                                        if (!$module) return 'Module not found';
                                        
                                        $lessonsCount = $module->lessons()->count();
                                        $totalDuration = $module->lessons()->sum('duration_minutes');
                                        
                                        return "**{$module->title}**\n\nLessons: {$lessonsCount}\nTotal Duration: {$totalDuration} minutes";
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('module_id')),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(25),
                    
                Tables\Columns\TextColumn::make('module.title')
                    ->label('Module')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(25),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('Lesson Title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('content_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video' => 'info',
                        'text' => 'success',
                        'pdf' => 'warning',
                        'quiz' => 'danger',
                        'live_class' => 'primary',
                        'assignment' => 'gray',
                        'resource' => 'secondary',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'video' => '🎥 Video',
                        'text' => '📝 Text',
                        'pdf' => '📄 PDF',
                        'quiz' => '❓ Quiz',
                        'live_class' => '🔴 Live Class',
                        'assignment' => '📋 Assignment',
                        'resource' => '📎 Resource',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('batch.batch_name')
                    ->label('Batch')
                    ->badge()
                    ->color('primary')
                    ->placeholder('All Batches')
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable()
                    ->alignCenter()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->color('gray'),
                    
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\IconColumn::make('is_free_preview')
                    ->label('Free')
                    ->boolean()
                    ->trueIcon('heroicon-o-gift')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('Not scheduled')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('module')
                    // ->relationship('module', 'title')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('content_type')
                    ->options([
                        'video' => '🎥 Video',
                        'text' => '📝 Text/Article',
                        'pdf' => '📄 PDF Document',
                        'quiz' => '❓ Quiz/Assessment',
                        'live_class' => '🔴 Live Class',
                        'assignment' => '📋 Assignment',
                        'resource' => '📎 Resource/Download',
                    ]),
                    
                Tables\Filters\SelectFilter::make('batch')
                    // ->relationship('batch', 'batch_name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published')
                    ->placeholder('All lessons')
                    ->trueLabel('Published lessons')
                    ->falseLabel('Draft lessons'),
                    
                Tables\Filters\TernaryFilter::make('is_free_preview')
                    ->label('Free Preview')
                    ->placeholder('All lessons')
                    ->trueLabel('Free preview lessons')
                    ->falseLabel('Paid lessons'),
                    
                Tables\Filters\TernaryFilter::make('is_batch_specific')
                    ->label('Batch Specific')
                    ->placeholder('All lessons')
                    ->trueLabel('Batch specific lessons')
                    ->falseLabel('General lessons'),
                    
                Tables\Filters\Filter::make('scheduled_date')
                    ->form([
                        Forms\Components\DatePicker::make('scheduled_from')
                            ->label('Scheduled from'),
                        Forms\Components\DatePicker::make('scheduled_until')
                            ->label('Scheduled until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->excludeAttributes(['title'])
                    ->beforeReplicaSaved(function (Lesson $replica): void {
                        $replica->title = $replica->title . ' (Copy)';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_published' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_published' => false]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('mark_free')
                        ->label('Mark as Free Preview')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_free_preview' => true]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        try {
            return static::getModel()::where('is_published', true)->count();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}