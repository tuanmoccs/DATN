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

      // Query leaderboard
      $topStudents = DB::table('grading as g')
          ->join('assignment_submissions as s', 'g.submission_id', '=', 's.id')
          ->whereIn('s.assignment_id', $assignmentIds)
          ->whereNotNull('g.score')
          ->select(
              's.student_id',
              DB::raw('MAX(g.percentage) as best_score'),
              DB::raw('ROUND(AVG(g.percentage), 2) as avg_score')
          )
          ->groupBy('s.student_id')
          ->orderByDesc('best_score')
          ->limit(10)
          ->get();

      if ($topStudents->isEmpty()) {
          return [];
      }

      $studentIds = $topStudents->pluck('student_id');

      // Load user 1 lần
      $students = DB::table('users')
          ->whereIn('id', $studentIds)
          ->select('id', 'name', 'email', 'avatar')
          ->get()
          ->keyBy('id');

      // Lấy bài có điểm cao nhất của mỗi student
      $bestSubmissions = DB::table('assignment_submissions as s')
          ->join('grading as g', 's.id', '=', 'g.submission_id')
          ->join('assignments as a', 's.assignment_id', '=', 'a.id')
          ->whereIn('s.assignment_id', $assignmentIds)
          ->whereIn('s.student_id', $studentIds)
          ->whereNotNull('g.score')
          ->select(
              's.student_id',
              'a.title as assignment_title',
              DB::raw('MAX(g.percentage) as max_percentage')
          )
          ->groupBy('s.student_id', 'a.title')
          ->get()
          ->groupBy('student_id')
          ->map(function ($items) {
              return $items->sortByDesc('max_percentage')->first();
          });

      //Map kết quả
      return $topStudents->map(function ($item) use ($students, $bestSubmissions) {
          $student = $students[$item->student_id] ?? null;
          if (!$student) return null;

          $bestSubmission = $bestSubmissions[$item->student_id] ?? null;

          return [
              'student' => [
                  'id' => $student->id,
                  'name' => $student->name,
                  'email' => $student->email,
                  'avatar' => $student->avatar,
              ],
              'best_score' => round((float) $item->best_score, 2),
              'avg_score' => round((float) $item->avg_score, 2),
              'assignment_title' => $bestSubmission->assignment_title ?? null,
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
