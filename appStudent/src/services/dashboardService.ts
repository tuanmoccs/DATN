import apiClient from './apiClient';

export interface StudentDashboardStats {
  active_classes: number;
  pending_classes: number;
  total_lessons: number;
  completed_lessons: number;
  average_quiz_score: number | null;
  pending_assignments: number;
}

export interface StudentDashboardClassItem {
  id: number;
  name: string;
  teacher_name: string | null;
  semester: string | null;
  joined_at?: string | null;
  requested_at?: string | null;
}

export interface StudentDashboardLessonItem {
  lesson_id: number;
  title: string;
  class_name: string | null;
  status: string;
  progress_percent: number;
  updated_at: string | null;
}

export interface StudentDashboardAssignmentItem {
  assignment_id: number;
  title: string;
  class_name: string | null;
  due_date: string | null;
  submission_type: string | null;
  status: string;
  submitted_at: string | null;
}

export interface StudentDashboardQuizAttemptItem {
  attempt_id: number;
  quiz_id: number;
  quiz_title: string | null;
  lesson_title: string | null;
  class_name: string | null;
  percentage: number | null;
  submitted_at: string | null;
}

export interface StudentDashboardData {
  stats: StudentDashboardStats;
  active_classes: StudentDashboardClassItem[];
  pending_classes: StudentDashboardClassItem[];
  recent_lessons: StudentDashboardLessonItem[];
  upcoming_assignments: StudentDashboardAssignmentItem[];
  recent_quiz_attempts: StudentDashboardQuizAttemptItem[];
}

export interface StudentDashboardResponse {
  success: boolean;
  data: StudentDashboardData;
}

const dashboardService = {
  getStudentDashboard: async (): Promise<StudentDashboardResponse> => {
    const response = await apiClient.get('/student/dashboard');
    return response.data;
  },
};

export default dashboardService;
