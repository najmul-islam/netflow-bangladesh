<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Course::class => \App\Policies\CoursePolicy::class,
        \App\Models\BatchEnrollment::class => \App\Policies\BatchEnrollmentPolicy::class,
        \App\Models\BatchAssessment::class => \App\Policies\BatchAssessmentPolicy::class,
        \App\Models\BatchAssessmentAttempt::class => \App\Policies\BatchAssessmentAttemptPolicy::class,
        \App\Models\BatchCertificate::class => \App\Policies\BatchCertificatePolicy::class,
        \App\Models\BatchForum::class => \App\Policies\BatchForumPolicy::class,
        \App\Models\BatchForumReply::class => \App\Policies\BatchForumReplyPolicy::class,
        \App\Models\BatchForumTopic::class => \App\Policies\BatchForumTopicPolicy::class,
        \App\Models\BatchInstructor::class => \App\Policies\BatchInstructorPolicy::class,
        \App\Models\BatchLessonProgress::class => \App\Policies\BatchLessonProgressPolicy::class,
        \App\Models\BatchMessage::class => \App\Policies\BatchMessagePolicy::class,
        \App\Models\BatchNotification::class => \App\Policies\BatchNotificationPolicy::class,
        \App\Models\BatchNotificationTemplate::class => \App\Policies\BatchNotificationTemplatePolicy::class,
        \App\Models\BatchQuestion::class => \App\Policies\BatchQuestionPolicy::class,
        \App\Models\BatchQuestionOption::class => \App\Policies\BatchQuestionOptionPolicy::class,
        \App\Models\BatchQuestionResponse::class => \App\Policies\BatchQuestionResponsePolicy::class,
        \App\Models\BatchReview::class => \App\Policies\BatchReviewPolicy::class,
        \App\Models\BatchSchedule::class => \App\Policies\BatchSchedulePolicy::class,
        \App\Models\Category::class => \App\Policies\CategoryPolicy::class,
        \App\Models\ClassAttendance::class => \App\Policies\ClassAttendancePolicy::class,
        \App\Models\CourseBatch::class => \App\Policies\CourseBatchPolicy::class,
        \App\Models\CourseInstructor::class => \App\Policies\CourseInstructorPolicy::class,
        \App\Models\Lesson::class => \App\Policies\LessonPolicy::class,
        \App\Models\LessonResource::class => \App\Policies\LessonResourcePolicy::class,
        \App\Models\Module::class => \App\Policies\ModulePolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\UserRole::class => \App\Policies\UserRolePolicy::class,
        \App\Models\Address::class => \App\Policies\AddressPolicy::class,
        \App\Models\BatchActivityLog::class => \App\Policies\BatchActivityLogPolicy::class,
        \App\Models\Tag::class => \App\Policies\TagPolicy::class,
        \App\Models\CourseInstructor::class => \App\Policies\CourseInstructorPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('access-admin-panel', function ($user) {
            return $user->hasAnyRole(['admin', 'instructor']);
        });

        Gate::define('access-user-panel', function ($user) {
            return $user->hasAnyRole(['student', 'instructor', 'admin']);
        });
    }
}