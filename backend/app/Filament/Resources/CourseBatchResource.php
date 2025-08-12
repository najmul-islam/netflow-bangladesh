<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseBatchResource\Pages;
use App\Filament\Resources\CourseBatchResource\RelationManagers;
use App\Models\CourseBatch;
use App\Models\Course;
use App\Models\User;
use App\Models\BatchEnrollment;
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

class CourseBatchResource extends Resource
{
    protected static ?string $model = CourseBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Course Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Course Batches';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Batch Management')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Batch Details')
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Section::make('Basic Information')
                                            ->description('Essential batch details')
                                            ->schema([
                                                Forms\Components\Select::make('course_id')
                                                    ->label('Course')
                                                    ->relationship('course', 'title')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                        if ($state) {
                                                            $course = Course::find($state);
                                                            if ($course) {
                                                                $batchCount = CourseBatch::where('course_id', $state)->count() + 1;
                                                                $batchCode = strtoupper(substr($course->title, 0, 3)) . '-' . str_pad($batchCount, 3, '0', STR_PAD_LEFT);
                                                                $set('batch_code', $batchCode);
                                                            }
                                                        }
                                                    })
                                                    ->placeholder('Select a course'),

                                                Forms\Components\TextInput::make('batch_name')
                                                    ->label('Batch Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('e.g., Morning Batch, Evening Batch')
                                                    ->live(onBlur: true),

                                                Forms\Components\TextInput::make('batch_code')
                                                    ->label('Batch Code')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->alphaDash()
                                                    ->placeholder('Auto-generated from course'),

                                                Forms\Components\Select::make('batch_type')
                                                    ->label('Batch Type')
                                                    ->options([
                                                        'regular' => 'Regular',
                                                        'intensive' => 'Intensive',
                                                        'weekend' => 'Weekend',
                                                        'evening' => 'Evening',
                                                        'fast_track' => 'Fast Track',
                                                        'part_time' => 'Part Time',
                                                    ])
                                                    ->default('regular')
                                                    ->required()
                                                    ->native(false),

                                                Forms\Components\Select::make('status')
                                                    ->label('Status')
                                                    ->options([
                                                        'draft' => 'Draft',
                                                        'open_for_enrollment' => 'Open for Enrollment',
                                                        'enrollment_closed' => 'Enrollment Closed',
                                                        'in_progress' => 'In Progress',
                                                        'completed' => 'Completed',
                                                        'cancelled' => 'Cancelled',
                                                        'postponed' => 'Postponed',
                                                    ])
                                                    ->default('draft')
                                                    ->required()
                                                    ->native(false),

                                                Forms\Components\Textarea::make('description')
                                                    ->label('Description')
                                                    ->rows(3)
                                                    ->placeholder('Brief description of the batch')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2),

                                        Forms\Components\Section::make('Schedule & Dates')
                                            ->description('Batch timeline and important dates')
                                            ->schema([
                                                Forms\Components\DatePicker::make('start_date')
                                                    ->label('Start Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                        if ($state) {
                                                            $enrollmentStart = \Carbon\Carbon::parse($state)->subDays(30);
                                                            $set('enrollment_start_date', $enrollmentStart);
                                                            
                                                            $enrollmentEnd = \Carbon\Carbon::parse($state)->subDays(3);
                                                            $set('enrollment_end_date', $enrollmentEnd);
                                                        }
                                                    }),

                                                Forms\Components\DatePicker::make('end_date')
                                                    ->label('End Date')
                                                    ->native(false)
                                                    ->after('start_date'),

                                                Forms\Components\DateTimePicker::make('enrollment_start_date')
                                                    ->label('Enrollment Start')
                                                    ->native(false)
                                                    ->before('enrollment_end_date'),

                                                Forms\Components\DateTimePicker::make('enrollment_end_date')
                                                    ->label('Enrollment End')
                                                    ->native(false)
                                                    ->before('start_date'),

                                                Forms\Components\Select::make('timezone')
                                                    ->label('Timezone')
                                                    ->options([
                                                        'UTC' => 'UTC',
                                                        'Asia/Dhaka' => 'Asia/Dhaka (GMT+6)',
                                                        'America/New_York' => 'America/New_York (EST/EDT)',
                                                        'Europe/London' => 'Europe/London (GMT/BST)',
                                                        'Asia/Kolkata' => 'Asia/Kolkata (IST)',
                                                        'Asia/Dubai' => 'Asia/Dubai (GST)',
                                                    ])
                                                    ->default('Asia/Dhaka')
                                                    ->searchable()
                                                    ->native(false),
                                            ])
                                            ->columns(2),

                                        Forms\Components\Section::make('Enrollment Settings')
                                            ->schema([
                                                Forms\Components\TextInput::make('max_students')
                                                    ->label('Maximum Students')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(1000)
                                                    ->default(50)
                                                    ->suffix('students')
                                                    ->helperText('Maximum enrollment capacity'),

                                                Forms\Components\TextInput::make('zoom_link')
                                                    ->label('Zoom/Meeting Link')
                                                    ->url()
                                                    ->placeholder('https://zoom.us/j/...')
                                                    ->helperText('Primary online meeting link for classes'),

                                                Forms\Components\Textarea::make('notes')
                                                    ->label('Additional Notes')
                                                    ->rows(3)
                                                    ->placeholder('Special instructions, requirements, or notes for this batch')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2),
                                    ])
                                    ->columnSpan(['lg' => 2]),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Section::make('Batch Statistics')
                                            ->schema([
                                                Forms\Components\Placeholder::make('enrollment_summary')
                                                    ->label('Enrollment Summary')
                                                    ->content(function ($record) {
                                                        if (!$record) return '**New Batch** - No enrollments yet';
                                                        
                                                        try {
                                                            $totalEnrollments = \DB::table('batch_enrollments')
                                                                ->where('batch_id', $record->batch_id)
                                                                ->count();
                                                            
                                                            $activeEnrollments = \DB::table('batch_enrollments')
                                                                ->where('batch_id', $record->batch_id)
                                                                ->where('status', 'active')
                                                                ->count();
                                                            
                                                            $pendingPayments = \DB::table('batch_enrollments')
                                                                ->where('batch_id', $record->batch_id)
                                                                ->where('payment_status', 'pending')
                                                                ->count();
                                                            
                                                            $completedPayments = \DB::table('batch_enrollments')
                                                                ->where('batch_id', $record->batch_id)
                                                                ->where('payment_status', 'paid')
                                                                ->count();

                                                            $maxStudents = $record->max_students ?? 50;
                                                            $percentage = $maxStudents > 0 ? round(($activeEnrollments / $maxStudents) * 100, 1) : 0;

                                                            return "**Enrollment Status**\n\n" .
                                                                   "👥 **Total Enrollments:** {$totalEnrollments}\n" .
                                                                   "✅ **Active Students:** {$activeEnrollments}\n" .
                                                                   "📊 **Capacity Used:** {$percentage}%\n\n" .
                                                                   "**Payment Status**\n\n" .
                                                                   "💰 **Paid:** {$completedPayments}\n" .
                                                                   "⏳ **Pending:** {$pendingPayments}";
                                                        } catch (\Exception $e) {
                                                            return '**Error loading statistics**';
                                                        }
                                                    }),

                                                Forms\Components\Placeholder::make('batch_overview')
                                                    ->label('Batch Overview')
                                                    ->content(function (Forms\Get $get) {
                                                        $courseId = $get('course_id');
                                                        $startDate = $get('start_date');
                                                        $endDate = $get('end_date');
                                                        
                                                        $stats = "**Batch Details**\n\n";
                                                        
                                                        if ($courseId) {
                                                            $course = Course::find($courseId);
                                                            $stats .= "📚 **Course:** " . ($course ? $course->title : 'Unknown') . "\n";
                                                        }
                                                        
                                                        if ($startDate && $endDate) {
                                                            $duration = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate));
                                                            $stats .= "⏱️ **Duration:** {$duration} days\n";
                                                        }
                                                        
                                                        if ($startDate) {
                                                            $daysUntilStart = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($startDate), false);
                                                            if ($daysUntilStart > 0) {
                                                                $stats .= "🚀 **Starts in:** {$daysUntilStart} days\n";
                                                            } elseif ($daysUntilStart < 0) {
                                                                $stats .= "✅ **Started:** " . abs($daysUntilStart) . " days ago\n";
                                                            } else {
                                                                $stats .= "🎯 **Starts:** Today!\n";
                                                            }
                                                        }
                                                        
                                                        return $stats;
                                                    })
                                                    ->visible(fn (Forms\Get $get) => $get('course_id')),
                                            ]),
                                    ])
                                    ->columnSpan(['lg' => 1]),
                            ])
                            ->columns(3),

                        Forms\Components\Tabs\Tab::make('Enrollments Management')
                            ->schema([
                                Forms\Components\Section::make('Quick Enrollment')
                                    ->description('Add new student to this batch')
                                    ->schema([
                                        Forms\Components\Select::make('new_student_id')
                                            ->label('Select Student')
                                            ->searchable()
                                            ->options(function () {
                                                try {
                                                    return \DB::table('users')
                                                        ->join('user_roles', 'users.user_id', '=', 'user_roles.user_id')
                                                        ->join('roles', 'user_roles.role_id', '=', 'roles.role_id')
                                                        ->where('roles.role_name', 'student')
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
                                                    return User::all()->mapWithKeys(function ($user) {
                                                        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                        if (empty($fullName)) {
                                                            $fullName = $user->email ?? 'Unknown';
                                                        }
                                                        return [$user->user_id => $fullName . ' (' . $user->email . ')'];
                                                    });
                                                }
                                            })
                                            ->placeholder('Search and select a student')
                                            ->helperText('Start typing to search students'),

                                        Forms\Components\Select::make('new_enrollment_status')
                                            ->label('Enrollment Status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'active' => 'Active',
                                                'completed' => 'Completed',
                                                'dropped' => 'Dropped',
                                                'suspended' => 'Suspended',
                                                'transferred' => 'Transferred',
                                            ])
                                            ->default('pending')
                                            ->required(),

                                        Forms\Components\Select::make('new_payment_status')
                                            ->label('Payment Status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'partial' => 'Partial',
                                                'paid' => 'Paid',
                                                'refunded' => 'Refunded',
                                                'failed' => 'Failed',
                                            ])
                                            ->default('pending')
                                            ->required(),

                                        Forms\Components\TextInput::make('new_amount_paid')
                                            ->label('Amount Paid')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0),

                                        Forms\Components\TextInput::make('new_progress_percentage')
                                            ->label('Progress %')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(0)
                                            ->suffix('%'),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('add_enrollment')
                                                ->label('Add Student to Batch')
                                                ->icon('heroicon-o-plus')
                                                ->color('success')
                                                ->action(function (array $data, $record) {
                                                    if (!$record || !isset($data['new_student_id'])) {
                                                        Notification::make()
                                                            ->title('Error')
                                                            ->body('Please save the batch first and select a student')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }

                                                    try {
                                                        // Check if student is already enrolled
                                                        $existingEnrollment = BatchEnrollment::where('batch_id', $record->batch_id)
                                                            ->where('user_id', $data['new_student_id'])
                                                            ->first();

                                                        if ($existingEnrollment) {
                                                            Notification::make()
                                                                ->title('Student Already Enrolled')
                                                                ->body('This student is already enrolled in this batch')
                                                                ->warning()
                                                                ->send();
                                                            return;
                                                        }

                                                        // Add new enrollment using the model
                                                        BatchEnrollment::create([
                                                            'batch_id' => $record->batch_id,
                                                            'user_id' => $data['new_student_id'],
                                                            'status' => $data['new_enrollment_status'] ?? 'pending',
                                                            'payment_status' => $data['new_payment_status'] ?? 'pending',
                                                            'progress_percentage' => $data['new_progress_percentage'] ?? 0,
                                                            'enrollment_date' => now(),
                                                            'enrolled_by' => auth()->id(),
                                                            'last_accessed' => now(),
                                                            'attendance_percentage' => 0,
                                                            'final_exam_passed' => false,
                                                            'final_exam_score' => 0,
                                                            'certificate_issued' => false,
                                                        ]);

                                                        // Update current_students count based on active enrollments
                                                        $activeCount = BatchEnrollment::where('batch_id', $record->batch_id)
                                                            ->where('status', 'active')
                                                            ->count();
                                                        
                                                        $record->update(['current_students' => $activeCount]);

                                                        Notification::make()
                                                            ->title('Student Enrolled Successfully')
                                                            ->body('The student has been added to this batch')
                                                            ->success()
                                                            ->send();

                                                    } catch (\Exception $e) {
                                                        Notification::make()
                                                            ->title('Error')
                                                            ->body('Failed to enroll student: ' . $e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                })
                                                ->visible(fn ($record) => $record !== null),
                                        ])
                                        ->columnSpanFull(),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Current Enrollments')
                                    ->description('Manage existing enrollments for this batch')
                                    ->schema([
                                        Forms\Components\Placeholder::make('enrollments_table')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) return '**Save the batch first to manage enrollments**';
                                                
                                                try {
                                                    $enrollments = BatchEnrollment::with('user')
                                                        ->where('batch_id', $record->batch_id)
                                                        ->orderBy('enrollment_date', 'desc')
                                                        ->get();

                                                    if ($enrollments->isEmpty()) {
                                                        return '**No enrollments yet**\n\nUse the form above to add students to this batch.';
                                                    }

                                                    $tableContent = "| Student | Status | Payment | Progress | Amount | Enrolled |\n";
                                                    $tableContent .= "|---------|--------|---------|----------|--------|----------|\n";
                                                    
                                                    foreach ($enrollments as $enrollment) {
                                                        $user = $enrollment->user;
                                                        $fullName = 'Unknown';
                                                        
                                                        if ($user) {
                                                            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                            if (empty($fullName)) {
                                                                $fullName = $user->email ?? 'Unknown';
                                                            }
                                                        }
                                                        
                                                        $statusBadge = match($enrollment->status) {
                                                            'active' => '🟢 Active',
                                                            'pending' => '🟡 Pending',
                                                            'completed' => '✅ Completed',
                                                            'dropped' => '🔴 Dropped',
                                                            'suspended' => '⏸️ Suspended',
                                                            'transferred' => '🔄 Transferred',
                                                            default => '❓ Unknown'
                                                        };
                                                        
                                                        $paymentBadge = match($enrollment->payment_status) {
                                                            'paid' => '💰 Paid',
                                                            'pending' => '⏳ Pending',
                                                            'partial' => '💸 Partial',
                                                            'refunded' => '↩️ Refunded',
                                                            'failed' => '❌ Failed',
                                                            default => '❓ Unknown'
                                                        };
                                                        
                                                        $progress = round($enrollment->progress_percentage ?? 0, 1) . '%';
                                                        $amount = '$' . number_format(0, 2); // No amount field in model, setting to 0
                                                        $enrolledDate = $enrollment->enrollment_date ? 
                                                            \Carbon\Carbon::parse($enrollment->enrollment_date)->format('M j, Y') : 
                                                            'Unknown';
                                                        
                                                        $tableContent .= "| {$fullName} | {$statusBadge} | {$paymentBadge} | {$progress} | {$amount} | {$enrolledDate} |\n";
                                                    }
                                                    
                                                    return $tableContent;

                                                } catch (\Exception $e) {
                                                    return '**Error loading enrollments:** ' . $e->getMessage();
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Bulk Operations')
                                    ->description('Perform bulk actions on enrollments')
                                    ->schema([
                                        Forms\Components\Select::make('bulk_action')
                                            ->label('Bulk Action')
                                            ->options([
                                                'activate_all' => 'Activate All Pending',
                                                'mark_paid' => 'Mark All as Paid',
                                                'complete_progress' => 'Set Progress to 100%',
                                                'reset_progress' => 'Reset Progress to 0%',
                                                'export_list' => 'Export Student List',
                                                'send_notifications' => 'Send Batch Notifications',
                                            ])
                                            ->placeholder('Select an action'),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('execute_bulk_action')
                                                ->label('Execute Action')
                                                ->icon('heroicon-o-bolt')
                                                ->color('warning')
                                                ->action(function (array $data, $record) {
                                                    if (!$record || !isset($data['bulk_action'])) {
                                                        return;
                                                    }

                                                    try {
                                                        switch ($data['bulk_action']) {
                                                            case 'activate_all':
                                                                BatchEnrollment::where('batch_id', $record->batch_id)
                                                                    ->where('status', 'pending')
                                                                    ->update([
                                                                        'status' => 'active',
                                                                        'last_accessed' => now()
                                                                    ]);
                                                                
                                                                // Update current_students count
                                                                $activeCount = BatchEnrollment::where('batch_id', $record->batch_id)
                                                                    ->where('status', 'active')
                                                                    ->count();
                                                                
                                                                $record->update(['current_students' => $activeCount]);
                                                                
                                                                Notification::make()
                                                                    ->title('All pending students activated')
                                                                    ->success()
                                                                    ->send();
                                                                break;

                                                            case 'mark_paid':
                                                                BatchEnrollment::where('batch_id', $record->batch_id)
                                                                    ->where('payment_status', 'pending')
                                                                    ->update(['payment_status' => 'paid']);
                                                                
                                                                Notification::make()
                                                                    ->title('All payments marked as paid')
                                                                    ->success()
                                                                    ->send();
                                                                break;

                                                            case 'complete_progress':
                                                                BatchEnrollment::where('batch_id', $record->batch_id)
                                                                    ->update([
                                                                        'progress_percentage' => 100,
                                                                        'last_accessed' => now()
                                                                    ]);
                                                                
                                                                Notification::make()
                                                                    ->title('All student progress set to 100%')
                                                                    ->success()
                                                                    ->send();
                                                                break;

                                                            case 'reset_progress':
                                                                BatchEnrollment::where('batch_id', $record->batch_id)
                                                                    ->update(['progress_percentage' => 0]);
                                                                
                                                                Notification::make()
                                                                    ->title('All student progress reset to 0%')
                                                                    ->success()
                                                                    ->send();
                                                                break;

                                                            default:
                                                                Notification::make()
                                                                    ->title('Action not implemented')
                                                                    ->body('This bulk action is not yet implemented')
                                                                    ->warning()
                                                                    ->send();
                                                        }
                                                    } catch (\Exception $e) {
                                                        Notification::make()
                                                            ->title('Error')
                                                            ->body('Failed to execute action: ' . $e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                })
                                                ->visible(fn ($record) => $record !== null),
                                        ]),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch_name')
                    ->label('Batch Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(30),

                Tables\Columns\TextColumn::make('batch_code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function ($record) {
                        return $record->course?->title;
                    }),

                Tables\Columns\TextColumn::make('batch_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'regular' => 'primary',
                        'intensive' => 'warning',
                        'weekend' => 'success',
                        'evening' => 'info',
                        'fast_track' => 'danger',
                        'part_time' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('enrollment_status')
                    ->label('Enrollment')
                    ->formatStateUsing(function ($record) {
                        try {
                            $current = BatchEnrollment::where('batch_id', $record->batch_id)
                                ->where('status', 'active')
                                ->count();
                            
                            $max = $record->max_students ?? 0;
                            $percentage = $max > 0 ? round(($current / $max) * 100) : 0;
                            
                            return "{$current}/{$max} ({$percentage}%)";
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->badge()
                    ->color(function ($record) {
                        try {
                            $current = BatchEnrollment::where('batch_id', $record->batch_id)
                                ->where('status', 'active')
                                ->count();
                            
                            $max = $record->max_students ?? 0;
                            $percentage = $max > 0 ? ($current / $max) * 100 : 0;
                            
                            return match (true) {
                                $percentage >= 90 => 'danger',
                                $percentage >= 75 => 'warning',
                                $percentage >= 50 => 'success',
                                default => 'gray',
                            };
                        } catch (\Exception $e) {
                            return 'gray';
                        }
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(function ($record) {
                        $startDate = \Carbon\Carbon::parse($record->start_date);
                        $now = \Carbon\Carbon::now();
                        
                        if ($startDate->isPast()) {
                            return 'success';
                        } elseif ($startDate->diffInDays($now) <= 7) {
                            return 'warning';
                        }
                        
                        return 'primary';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'open_for_enrollment' => 'success',
                        'enrollment_closed' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'postponed' => 'warning',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('payment_summary')
                    ->label('Payments')
                    ->formatStateUsing(function ($record) {
                        try {
                            $paid = BatchEnrollment::where('batch_id', $record->batch_id)
                                ->where('payment_status', 'paid')
                                ->count();
                            
                            $pending = BatchEnrollment::where('batch_id', $record->batch_id)
                                ->where('payment_status', 'pending')
                                ->count();
                            
                            return "✅{$paid} ⏳{$pending}";
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('avg_progress')
                    ->label('Avg Progress')
                    ->formatStateUsing(function ($record) {
                        try {
                            $avgProgress = BatchEnrollment::where('batch_id', $record->batch_id)
                                ->avg('progress_percentage');
                            
                            return round($avgProgress ?? 0, 1) . '%';
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open_for_enrollment' => 'Open for Enrollment',
                        'enrollment_closed' => 'Enrollment Closed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'postponed' => 'Postponed',
                    ]),

                Tables\Filters\SelectFilter::make('batch_type')
                    ->options([
                        'regular' => 'Regular',
                        'intensive' => 'Intensive',
                        'weekend' => 'Weekend',
                        'evening' => 'Evening',
                        'fast_track' => 'Fast Track',
                        'part_time' => 'Part Time',
                    ]),

                Tables\Filters\Filter::make('has_enrollments')
                    ->label('Has Enrollments')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereExists(function ($subQuery) {
                            $subQuery->select(\DB::raw(1))
                                ->from('batch_enrollments')
                                ->whereColumn('batch_enrollments.batch_id', 'course_batches.batch_id');
                        })),

                Tables\Filters\Filter::make('payment_pending')
                    ->label('Pending Payments')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereExists(function ($subQuery) {
                            $subQuery->select(\DB::raw(1))
                                ->from('batch_enrollments')
                                ->whereColumn('batch_enrollments.batch_id', 'course_batches.batch_id')
                                ->where('batch_enrollments.payment_status', 'pending');
                        })),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('manage_enrollments')
                    ->label('Manage')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record, 'activeTab' => 'enrollments-management'])),

                Tables\Actions\Action::make('quick_stats')
                    ->label('Stats')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->modalContent(function ($record) {
                        try {
                            $enrollments = BatchEnrollment::where('batch_id', $record->batch_id)->get();
                            
                            $stats = [
                                'total' => $enrollments->count(),
                                'active' => $enrollments->where('status', 'active')->count(),
                                'pending' => $enrollments->where('status', 'pending')->count(),
                                'completed' => $enrollments->where('status', 'completed')->count(),
                                'paid' => $enrollments->where('payment_status', 'paid')->count(),
                                'payment_pending' => $enrollments->where('payment_status', 'pending')->count(),
                                'avg_progress' => round($enrollments->avg('progress_percentage') ?? 0, 1),
                            ];
                            
                            $content = "**Batch Statistics**\n\n";
                            $content .= "👥 **Total Enrollments:** {$stats['total']}\n";
                            $content .= "✅ **Active Students:** {$stats['active']}\n";
                            $content .= "🟡 **Pending Students:** {$stats['pending']}\n";
                            $content .= "🎓 **Completed Students:** {$stats['completed']}\n\n";
                            $content .= "💰 **Paid Students:** {$stats['paid']}\n";
                            $content .= "⏳ **Payment Pending:** {$stats['payment_pending']}\n\n";
                            $content .= "📊 **Average Progress:** {$stats['avg_progress']}%";
                            
                            return $content;
                        } catch (\Exception $e) {
                            return 'Error loading statistics: ' . $e->getMessage();
                        }
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('open_enrollment')
                        ->label('Open Enrollment')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'open_for_enrollment']))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('close_enrollment')
                        ->label('Close Enrollment')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['status' => 'enrollment_closed']))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseBatches::route('/'),
            'create' => Pages\CreateCourseBatch::route('/create'),
            'edit' => Pages\EditCourseBatch::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $activeCount = static::getModel()::whereIn('status', ['open_for_enrollment', 'in_progress'])->count();
            return $activeCount > 0 ? (string) $activeCount : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}