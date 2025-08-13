<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchAssessmentResource\Pages;
use App\Filament\Resources\BatchAssessmentResource\RelationManagers;
use App\Models\BatchAssessment;
use App\Models\CourseBatch;
use App\Models\Lesson;
use App\Models\BatchQuestion;
use App\Models\BatchQuestionOption;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;

class BatchAssessmentResource extends Resource
{
    protected static ?string $model = BatchAssessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Assessment Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Batch Assessments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Assessment Management')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Assessment Details')
                            ->schema([
                                Forms\Components\Section::make('Basic Information')
                                    ->description('Assessment overview and settings')
                                    ->schema([
                                        Forms\Components\Select::make('batch_id')
                                            ->label('Course Batch')
                                            ->relationship('batch', 'batch_name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state) {
                                                    // Auto-generate title based on batch
                                                    $batch = CourseBatch::find($state);
                                                    if ($batch) {
                                                        $assessmentCount = BatchAssessment::where('batch_id', $state)->count() + 1;
                                                        $title = $batch->batch_name . ' - Assessment ' . $assessmentCount;
                                                        $set('title', $title);
                                                    }
                                                }
                                            }),

                                        Forms\Components\Select::make('lesson_id')
                                            ->label('Related Lesson (Optional)')
                                            ->relationship('lesson', 'title')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Select if assessment is tied to specific lesson'),

                                        Forms\Components\TextInput::make('title')
                                            ->label('Assessment Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g., Module 1 Quiz, Final Exam'),

                                        Forms\Components\Select::make('assessment_type')
                                            ->label('Assessment Type')
                                            ->options([
                                                'quiz' => 'Quiz',
                                                'assignment' => 'Assignment',
                                                'exam' => 'Exam',
                                                'final_exam' => 'Final Exam',
                                                'survey' => 'Survey',
                                                'project' => 'Project',
                                                'presentation' => 'Presentation',
                                            ])
                                            ->default('quiz')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                // Auto-configure based on type
                                                switch ($state) {
                                                    case 'quiz':
                                                        $set('time_limit_minutes', 30);
                                                        $set('max_attempts', 3);
                                                        $set('passing_score', 70);
                                                        break;
                                                    case 'exam':
                                                        $set('time_limit_minutes', 120);
                                                        $set('max_attempts', 1);
                                                        $set('passing_score', 60);
                                                        $set('is_proctored', true);
                                                        break;
                                                    case 'final_exam':
                                                        $set('time_limit_minutes', 180);
                                                        $set('max_attempts', 1);
                                                        $set('passing_score', 50);
                                                        $set('is_final_exam', true);
                                                        $set('is_proctored', true);
                                                        break;
                                                    case 'assignment':
                                                        $set('time_limit_minutes', null);
                                                        $set('max_attempts', 5);
                                                        $set('allow_late_submission', true);
                                                        break;
                                                }
                                            }),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(3)
                                            ->placeholder('Brief description of the assessment'),

                                        Forms\Components\Textarea::make('instructions')
                                            ->label('Instructions for Students')
                                            ->rows(4)
                                            ->placeholder('Detailed instructions on how to complete the assessment')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Timing & Attempts')
                                    ->description('Configure timing and attempt settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('time_limit_minutes')
                                            ->label('Time Limit (Minutes)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(999)
                                            ->placeholder('Leave blank for no time limit')
                                            ->helperText('Students will have this much time to complete'),

                                        Forms\Components\TextInput::make('max_attempts')
                                            ->label('Maximum Attempts')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(10)
                                            ->default(1)
                                            ->required(),

                                        Forms\Components\DateTimePicker::make('available_from')
                                            ->label('Available From')
                                            ->native(false)
                                            ->placeholder('When students can start'),

                                        Forms\Components\DateTimePicker::make('available_until')
                                            ->label('Available Until')
                                            ->native(false)
                                            ->placeholder('Last chance to start')
                                            ->after('available_from'),

                                        Forms\Components\DateTimePicker::make('due_date')
                                            ->label('Due Date')
                                            ->native(false)
                                            ->placeholder('When assessment must be completed')
                                            ->after('available_from'),
                                    ])
                                    ->columns(2),

                              
                            ]),

                       Forms\Components\Tabs\Tab::make('Questions & Options')
                            ->schema([
                                Forms\Components\Section::make('Assessment Questions')
                                    ->description('Add multiple choice questions for this assessment')
                                    ->schema([
                                        Forms\Components\Repeater::make('questions')
                                            ->relationship('questions')
                                            ->schema([
                                                Forms\Components\Grid::make()
                                                    ->schema([
                                                        Forms\Components\Textarea::make('question_text')
                                                            ->label('Question')
                                                            ->required()
                                                            ->rows(3)
                                                            ->placeholder('Enter your question here...')
                                                            ->columnSpan(3),

                                                        Forms\Components\TextInput::make('points')
                                                            ->label('Points')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->required()
                                                            ->columnSpan(1),

                                                        Forms\Components\TextInput::make('sort_order')
                                                            ->label('Order')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->required()
                                                            ->columnSpan(1),

                                                        // Hidden defaults for MCQ
                                                        Forms\Components\Hidden::make('question_type')
                                                            ->default('single_choice'),
                                                        Forms\Components\Hidden::make('difficulty_level')
                                                            ->default('medium'),
                                                        Forms\Components\Hidden::make('is_required')
                                                            ->default(true),
                                                    ])
                                                    ->columns(5),

                                                // Answer Options
                                                Forms\Components\Section::make('Answer Options')
                                                    ->schema([
                                                        Forms\Components\Repeater::make('options')
                                                            ->relationship('options')
                                                            ->schema([
                                                                Forms\Components\Grid::make()
                                                                    ->schema([
                                                                        Forms\Components\Textarea::make('option_text')
                                                                            ->label('Option')
                                                                            ->required()
                                                                            ->rows(2)
                                                                            ->placeholder('Enter option text...')
                                                                            ->columnSpan(3),

                                                                        Forms\Components\Toggle::make('is_correct')
                                                                            ->label('Correct')
                                                                            ->columnSpan(1),

                                                                        Forms\Components\TextInput::make('sort_order')
                                                                            ->label('Order')
                                                                            ->numeric()
                                                                            ->default(1)
                                                                            ->columnSpan(1),
                                                                    ])
                                                                    ->columns(5),
                                                            ])
                                                            ->addActionLabel('Add Option')
                                                            ->defaultItems(4)
                                                            ->minItems(2)
                                                            ->maxItems(6)
                                                            ->reorderable('sort_order')
                                                            ->orderColumn('sort_order')
                                                            ->itemLabel(fn (array $state): ?string => $state['option_text'] ?? 'New Option')
                                                            ->collapsible()
                                                            ->collapsed(false),
                                                    ])
                                                    ->collapsible()
                                                    ->collapsed(false),
                                            ])
                                            ->addActionLabel('Add Question')
                                            ->defaultItems(1)
                                            ->reorderable('sort_order')
                                            ->orderColumn('sort_order')
                                            ->itemLabel(fn (array $state): ?string => 
                                                $state['question_text'] ? 
                                                    (strlen($state['question_text']) > 60 ? 
                                                        substr($state['question_text'], 0, 60) . '...' : 
                                                        $state['question_text']) 
                                                    : 'New Question')
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->columnSpanFull(),
                                    ])

                               
                                    ->compact(),
                            ]),

                        
                        
                            Forms\Components\Tabs\Tab::make('Assessment Preview')
                            ->schema([
                                Forms\Components\Section::make('Assessment Summary')
                                    ->schema([
                                        Forms\Components\Placeholder::make('assessment_summary')
                                            ->label('')
                                            ->content(function (Forms\Get $get, $record) {
                                                $title = $get('title') ?: 'Untitled Assessment';
                                                $type = $get('assessment_type') ?: 'quiz';
                                                $timeLimit = $get('time_limit_minutes');
                                                $maxAttempts = $get('max_attempts') ?: 1;
                                                $passingScore = $get('passing_score') ?: 70;
                                                $questions = $get('questions') ?? [];
                                                
                                                $totalPoints = array_sum(array_column($questions, 'points'));
                                                $questionCount = count($questions);
                                                
                                                $timeLimitText = $timeLimit ? "{$timeLimit} minutes" : "No time limit";
                                                
                                                $summary = "# {$title}\n\n";
                                                $summary .= "**Type:** " . ucfirst(str_replace('_', ' ', $type)) . "\n";
                                                $summary .= "**Questions:** {$questionCount}\n";
                                                $summary .= "**Total Points:** {$totalPoints}\n";
                                                $summary .= "**Time Limit:** {$timeLimitText}\n";
                                                $summary .= "**Max Attempts:** {$maxAttempts}\n";
                                                $summary .= "**Passing Score:** {$passingScore}%\n\n";
                                                
                                                return $summary;
                                            })
                                            ->columnSpanFull(),
                                    ]),

Forms\Components\Section::make('Questions Preview')
    ->description('Questions overview - edit in "Questions & Options" tab')
    ->schema([
        Forms\Components\View::make('filament.components.assessment-questions-table')
            ->view('filament.components.assessment-questions-table')
            ->viewData(function (Forms\Get $get) {
                $questions = $get('questions') ?? [];
                
                if (empty($questions)) {
                    return ['questions' => []];
                }
                
                // Sort by sort_order safely
                usort($questions, function($a, $b) {
                    $aOrder = (int)($a['sort_order'] ?? 999);
                    $bOrder = (int)($b['sort_order'] ?? 999);
                    return $aOrder <=> $bOrder;
                });
                
                return ['questions' => $questions];
            })
            ->columnSpanFull(),

        Forms\Components\Actions::make([
            Forms\Components\Actions\Action::make('reorder_questions')
                ->label('Auto-Reorder Questions (1,2,3...)')
                ->icon('heroicon-o-bars-3')
                ->color('warning')
                ->action(function (Forms\Set $set, Forms\Get $get) {
                    $questions = $get('questions') ?? [];
                    
                    if (!empty($questions)) {
                        foreach ($questions as $index => $question) {
                            $questions[$index]['sort_order'] = $index + 1;
                            
                            // Also reorder options
                            if (isset($questions[$index]['options']) && is_array($questions[$index]['options'])) {
                                foreach ($questions[$index]['options'] as $optIndex => $option) {
                                    $questions[$index]['options'][$optIndex]['sort_order'] = $optIndex + 1;
                                }
                            }
                        }
                        
                        $set('questions', $questions);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Questions Reordered')
                            ->body('All questions and options have been reordered sequentially.')
                            ->success()
                            ->send();
                    }
                })
                ->visible(fn (Forms\Get $get) => !empty($get('questions'))),

            Forms\Components\Actions\Action::make('add_sample_question')
                ->label('Add Sample Question')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->action(function (Forms\Set $set, Forms\Get $get) {
                    $existingQuestions = $get('questions') ?? [];
                    $questionNumber = count($existingQuestions) + 1;
                    
                    $newQuestion = [
                        'question_text' => "Sample Question {$questionNumber}: What is the correct answer?",
                        'question_type' => 'single_choice',
                        'points' => 1,
                        'difficulty_level' => 'medium',
                        'is_required' => true,
                        'sort_order' => $questionNumber,
                        'options' => [
                            [
                                'option_text' => 'Option A (Correct)',
                                'is_correct' => true,
                                'sort_order' => 1
                            ],
                            [
                                'option_text' => 'Option B (Incorrect)',
                                'is_correct' => false,
                                'sort_order' => 2
                            ],
                            [
                                'option_text' => 'Option C (Incorrect)',
                                'is_correct' => false,
                                'sort_order' => 3
                            ],
                            [
                                'option_text' => 'Option D (Incorrect)',
                                'is_correct' => false,
                                'sort_order' => 4
                            ],
                        ]
                    ];
                    
                    $set('questions', array_merge($existingQuestions, [$newQuestion]));
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Sample Question Added')
                        ->body('A sample question with 4 options has been added.')
                        ->success()
                        ->send();
                }),
        ]),
    ]),
                                Forms\Components\Section::make('Assessment Statistics')
                                    ->schema([
                                        Forms\Components\Placeholder::make('statistics')
                                            ->content(function (Forms\Get $get) {
                                                $questions = $get('questions') ?? [];
                                                
                                                if (empty($questions)) {
                                                    return '_No statistics available - add questions first._';
                                                }
                                                
                                                $totalQuestions = count($questions);
                                                $totalPoints = array_sum(array_column($questions, 'points'));
                                                $avgPoints = $totalQuestions > 0 ? round($totalPoints / $totalQuestions, 1) : 0;
                                                
                                                // Question type breakdown
                                                $typeBreakdown = [];
                                                foreach ($questions as $q) {
                                                    $type = $q['question_type'] ?? 'unknown';
                                                    $typeBreakdown[$type] = ($typeBreakdown[$type] ?? 0) + 1;
                                                }
                                                
                                                // Difficulty breakdown
                                                $difficultyBreakdown = [];
                                                foreach ($questions as $q) {
                                                    $difficulty = $q['difficulty_level'] ?? 'medium';
                                                    $difficultyBreakdown[$difficulty] = ($difficultyBreakdown[$difficulty] ?? 0) + 1;
                                                }
                                                
                                                $stats = "## Question Statistics\n\n";
                                                $stats .= "📊 **Total Questions:** {$totalQuestions}\n";
                                                $stats .= "🎯 **Total Points:** {$totalPoints}\n";
                                                $stats .= "📈 **Average Points per Question:** {$avgPoints}\n\n";
                                                
                                                $stats .= "### Question Types\n";
                                                foreach ($typeBreakdown as $type => $count) {
                                                    $typeLabel = ucfirst(str_replace('_', ' ', $type));
                                                    $percentage = round(($count / $totalQuestions) * 100, 1);
                                                    $stats .= "- **{$typeLabel}:** {$count} ({$percentage}%)\n";
                                                }
                                                
                                                $stats .= "\n### Difficulty Distribution\n";
                                                foreach ($difficultyBreakdown as $difficulty => $count) {
                                                    $icon = match($difficulty) {
                                                        'easy' => '🟢',
                                                        'medium' => '🟡',
                                                        'hard' => '🔴',
                                                        default => '⚪'
                                                    };
                                                    $percentage = round(($count / $totalQuestions) * 100, 1);
                                                    $stats .= "- {$icon} **" . ucfirst($difficulty) . ":** {$count} ({$percentage}%)\n";
                                                }
                                                
                                                return $stats;
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Assessment Title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(40),

                Tables\Columns\TextColumn::make('batch.batch_name')
                    ->label('Batch')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('assessment_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'quiz' => 'primary',
                        'exam' => 'warning',
                        'final_exam' => 'danger',
                        'assignment' => 'success',
                        'survey' => 'info',
                        'project' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->getStateUsing(function ($record) {
                        return $record->questions()->count();
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_points')
                    ->label('Total Points')
                    ->getStateUsing(function ($record) {
                        return $record->questions()->sum('points');
                    })
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('time_limit_minutes')
                    ->label('Time Limit')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : 'No limit')
                    ->color(fn ($state) => $state ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Pass %')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_attempts')
                    ->label('Max Attempts')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_final_exam')
                    ->label('Final')
                    ->boolean()
                    ->trueIcon('heroicon-o-academic-cap')
                    ->falseIcon('heroicon-o-academic-cap')
                    ->trueColor('danger')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->color(function ($record) {
                        if (!$record->due_date) return 'gray';
                        return $record->due_date->isPast() ? 'danger' : 'success';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'batch_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assessment_type')
                    ->options([
                        'quiz' => 'Quiz',
                        'assignment' => 'Assignment',
                        'exam' => 'Exam',
                        'final_exam' => 'Final Exam',
                        'survey' => 'Survey',
                        'project' => 'Project',
                        'presentation' => 'Presentation',
                    ]),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published')
                    ->placeholder('All assessments')
                    ->trueLabel('Published only')
                    ->falseLabel('Drafts only'),

                Tables\Filters\TernaryFilter::make('is_final_exam')
                    ->label('Final Exam')
                    ->placeholder('All assessments')
                    ->trueLabel('Final exams only')
                    ->falseLabel('Regular assessments'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function ($record) {
                        $newAssessment = $record->replicate();
                        $newAssessment->title = $record->title . ' (Copy)';
                        $newAssessment->is_published = false;
                        $newAssessment->save();

                        // Duplicate questions
                        foreach ($record->questions as $question) {
                            $newQuestion = $question->replicate();
                            $newQuestion->assessment_id = $newAssessment->assessment_id;
                            $newQuestion->save();

                            // Duplicate options
                            foreach ($question->options as $option) {
                                $newOption = $option->replicate();
                                $newOption->question_id = $newQuestion->question_id;
                                $newOption->save();
                            }
                        }

                        Notification::make()
                            ->title('Assessment duplicated successfully')
                            ->body('All questions and options have been copied')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('toggle_publish')
                    ->label(fn ($record) => $record->is_published ? 'Unpublish' : 'Publish')
                    ->icon(fn ($record) => $record->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_published ? 'warning' : 'success')
                    ->action(fn ($record) => $record->update(['is_published' => !$record->is_published]))
                    ->requiresConfirmation(),
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
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchAssessments::route('/'),
            'create' => Pages\CreateBatchAssessment::route('/create'),
            'edit' => Pages\EditBatchAssessment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $draftCount = static::getModel()::where('is_published', false)->count();
            return $draftCount > 0 ? (string) $draftCount : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}