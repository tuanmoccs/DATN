<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classz;
use App\Models\Enrollment;
use App\Models\Grading;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

class DashboardService
{
  /**
   * Get teacher dashboard statistics and top students
   */
  public function getTeacherDashboard(int $teacherId): array
  {
    $classIds = Classz::where('teacher_id', $teacherId)
      ->pluck('id')
      ->toArray();

    $stats = $this->getStats($teacherId, $classIds);
    $topQuizStudents = $this->getTopQuizStudents($classIds);
    $topAssignmentStudents = $this->getTopAssignmentStudents($classIds);
    $recentActivity = $this->getRecentActivity($classIds);

    return [
      'status' => 200,
      'data' => [
        'stats' => $stats,
        'top_quiz_students' => $topQuizStudents,
        'top_assignment_students' => $topAssignmentStudents,
        'recent_activity' => $recentActivity,
      ],
    ];
  }

  private function getStats(int $teacherId, array $classIds): array
  {
    $totalClasses = count($classIds);

    $totalStudents = $classIds
      ? Enrollment::whereIn('class_id', $classIds)
      ->where('status', 'active')
      ->distinct('user_id')
      ->count('user_id')
      : 0;

    $totalLessons = $classIds
      ? Lesson::whereIn('class_id', $classIds)->count()
      : 0;

    $lessonIds = $classIds
      ? Lesson::whereIn('class_id', $classIds)->pluck('id')->toArray()
      : [];

    $totalQuizzes = $lessonIds
      ? Quiz::whereIn('lesson_id', $lessonIds)->count()
      : 0;

    $totalAssignments = $classIds
      ? Assignment::whereIn('class_id', $classIds)->count()
      : 0;

    return [
      'total_classes' => $totalClasses,
      'total_students' => $totalStudents,
      'total_lessons' => $totalLessons,
      'total_quizzes' => $totalQuizzes,
      'total_assignments' => $totalAssignments,
    ];
  }

  /**
   * Get students with outstanding quiz scores across teacher's classes
   */
  private function getTopQuizStudents(array $classIds): array
  {
    if (empty($classIds)) {
      return [];
    }

    $lessonIds = Lesson::whereIn('class_id', $classIds)->pluck('id');
    $quizIds = Quiz::whereIn('lesson_id', $lessonIds)->pluck('id');

    if ($quizIds->isEmpty()) {
      return [];
    }

    return QuizAttempt::select(
      'student_id',
      DB::raw('MAX(percentage) as best_score'),
      DB::raw('ROUND(AVG(percentage), 2) as avg_score'),
      DB::raw('COUNT(*) as total_attempts')
    )
      ->whereIn('quiz_id', $quizIds)
      ->whereIn('status', ['submitted', 'graded'])
      ->groupBy('student_id')
      ->orderByDesc('best_score')
      ->limit(10)
      ->with(['student:id,name,email,avatar'])
      ->get()
      ->map(function ($attempt) use ($quizIds) {
        // Get latest quiz info for display
        $latestAttempt = QuizAttempt::where('student_id', $attempt->student_id)
          ->whereIn('quiz_id', $quizIds)
          ->whereIn('status', ['submitted', 'graded'])
          ->orderByDesc('percentage')
          ->first();

        return [
          'student' => $attempt->student ? [
            'id' => $attempt->student->id,
            'name' => $attempt->student->name,
            'email' => $attempt->student->email,
            'avatar' => $attempt->student->avatar,
          ] : null,
          'best_score' => round((float) $attempt->best_score, 2),
          'avg_score' => round((float) $attempt->avg_score, 2),
          'total_attempts' => $attempt->total_attempts,
          'quiz_title' => $latestAttempt?->quiz?->title,
        ];
      })
      ->filter(fn($item) => $item['student'] !== null)
      ->values()
      ->toArray();
  }

  /**
   * Get students with outstanding assignment scores across teacher's classes
   */
  private function getTopAssignmentStudents(array $classIds): array
  {
    if (empty($classIds)) {
      return [];
    }

    $assignmentIds = Assignment::whereIn('class_id', $classIds)->pluck('id');

    if ($assignmentIds->isEmpty()) {
      return [];
    }

    $submissionIds = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
      ->pluck('id');

    if ($submissionIds->isEmpty()) {
      return [];
    }

    return Grading::select(
      'gradings.submission_id',
      'assignment_submissions.student_id',
      DB::raw('MAX(gradings.percentage) as best_score'),
      DB::raw('ROUND(AVG(gradings.percentage), 2) as avg_score')
    )
      ->join('assignment_submissions', 'gradings.submission_id', '=', 'assignment_submissions.id')
      ->whereIn('gradings.submission_id', $submissionIds)
      ->whereNotNull('gradings.score')
      ->groupBy('assignment_submissions.student_id')
      ->orderByDesc('best_score')
      ->limit(10)
      ->get()
      ->map(function ($grading) use ($assignmentIds) {
        $student = \App\Models\User::find($grading->student_id);
        if (!$student) return null;

        $bestSubmission = AssignmentSubmission::where('student_id', $grading->student_id)
          ->whereIn('assignment_id', $assignmentIds)
          ->whereHas('grading', fn($q) => $q->whereNotNull('score'))
          ->with(['assignment:id,title', 'grading'])
          ->orderByDesc(
            Grading::select('percentage')
              ->whereColumn('submission_id', 'assignment_submissions.id')
              ->limit(1)
          )
          ->first();

        return [
          'student' => [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'avatar' => $student->avatar,
          ],
          'best_score' => round((float) $grading->best_score, 2),
          'avg_score' => round((float) $grading->avg_score, 2),
          'assignment_title' => $bestSubmission?->assignment?->title,
        ];
      })
      ->filter()
      ->values()
      ->toArray();
  }

  /**
   * Get recent activity across teacher's classes
   */
  private function getRecentActivity(array $classIds): array
  {
    if (empty($classIds)) {
      return [];
    }

    $activities = [];

    // Recent enrollments
    $recentEnrollments = Enrollment::whereIn('class_id', $classIds)
      ->where('status', 'active')
      ->orderByDesc('updated_at')
      ->limit(5)
      ->with(['user:id,name,avatar', 'class:id,name'])
      ->get()
      ->map(fn($e) => [
        'type' => 'enrollment',
        'message' => ($e->user->name ?? 'Unknown') . ' joined ' . ($e->class->name ?? 'a class'),
        'student_name' => $e->user->name ?? 'Unknown',
        'class_name' => $e->class->name ?? '',
        'date' => $e->updated_at->toISOString(),
      ]);

    $activities = array_merge($activities, $recentEnrollments->toArray());

    // Recent quiz attempts
    $lessonIds = Lesson::whereIn('class_id', $classIds)->pluck('id');
    $quizIds = Quiz::whereIn('lesson_id', $lessonIds)->pluck('id');

    if ($quizIds->isNotEmpty()) {
      $recentQuizAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)
        ->whereIn('status', ['submitted', 'graded'])
        ->orderByDesc('submitted_at')
        ->limit(5)
        ->with(['student:id,name,avatar', 'quiz:id,title'])
        ->get()
        ->map(fn($a) => [
          'type' => 'quiz_attempt',
          'message' => ($a->student->name ?? 'Unknown') . ' completed ' . ($a->quiz->title ?? 'a quiz') . ' — ' . round($a->percentage, 1) . '%',
          'student_name' => $a->student->name ?? 'Unknown',
          'quiz_title' => $a->quiz->title ?? '',
          'score' => round((float) $a->percentage, 1),
          'date' => $a->submitted_at?->toISOString() ?? $a->created_at->toISOString(),
        ]);

      $activities = array_merge($activities, $recentQuizAttempts->toArray());
    }

    // Recent assignment submissions
    $assignmentIds = Assignment::whereIn('class_id', $classIds)->pluck('id');

    if ($assignmentIds->isNotEmpty()) {
      $recentSubmissions = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
        ->orderByDesc('submitted_at')
        ->limit(5)
        ->with(['student:id,name,avatar', 'assignment:id,title'])
        ->get()
        ->map(fn($s) => [
          'type' => 'assignment_submission',
          'message' => ($s->student->name ?? 'Unknown') . ' submitted ' . ($s->assignment->title ?? 'an assignment'),
          'student_name' => $s->student->name ?? 'Unknown',
          'assignment_title' => $s->assignment->title ?? '',
          'date' => $s->submitted_at?->toISOString() ?? $s->created_at->toISOString(),
        ]);

      $activities = array_merge($activities, $recentSubmissions->toArray());
    }

    // Sort by date descending and take top 10
    usort($activities, fn($a, $b) => strcmp($b['date'], $a['date']));

    return array_slice($activities, 0, 10);
  }
}
