import apiClient from './apiClient';
import API_CONFIG from '../config/api';

// ==========================================
// Types
// ==========================================

export interface SlideInfo {
  id: number;
  order: number;
  title: string;
  content: string;
  notes: string | null;
  layout: string;
  image_url: string | null;
}

export interface QuizOverview {
  id: number;
  title: string;
  description: string | null;
  time_limit: number | null;
  max_attempts: number | null;
  question_count: number;
  total_points: number;
  attempt_count: number;
  can_attempt: boolean;
  best_score: number | null;
  latest_attempt: {
    id: number;
    score: number;
    percentage: number;
    status: string;
    submitted_at: string;
  } | null;
}

export interface LessonProgress {
  status: string;
  slides_viewed: number;
  total_slides: number;
  slides_completed: boolean;
  quiz_completed: boolean;
  lesson_completed: boolean;
  time_spent: number;
  started_at: string | null;
  completed_at: string | null;
}

export interface LessonDetail {
  id: number;
  title: string;
  description: string | null;
  objectives: string | null;
  slides: SlideInfo[];
  quizzes: QuizOverview[];
  progress: LessonProgress;
}

export interface QuizOption {
  id: number;
  order: number;
  option_text: string;
}

export interface QuizQuestion {
  id: number;
  order: number;
  content: string;
  question_type: string;
  points: number;
  options: QuizOption[];
}

export interface QuizStartData {
  attempt_id: number;
  quiz_title: string;
  time_limit: number | null;
  started_at: string;
  questions: QuizQuestion[];
}

export interface QuizSubmitResult {
  attempt_id: number;
  score: number;
  total_points: number;
  percentage: number;
  status: string;
}

export interface ResultOption {
  id: number;
  order: number;
  option_text: string;
  is_selected: boolean;
  is_correct?: boolean;
}

export interface ResultQuestion {
  question_id: number;
  content: string;
  points: number;
  points_earned: number;
  is_correct: boolean;
  options: ResultOption[];
  explanation?: string;
}

export interface QuizResultData {
  attempt_id: number;
  quiz_title: string;
  score: number;
  total_points: number;
  percentage: number;
  started_at: string;
  submitted_at: string;
  questions: ResultQuestion[];
  show_answers: boolean;
}

export interface LessonChatMessage {
  role: 'user' | 'assistant';
  content: string;
}

export interface LessonChatResponse {
  answer: string;
  sources: string[];
}

// ==========================================
// Service
// ==========================================

const STORAGE_BASE_URL = API_CONFIG.BASE_URL.replace(/\/api\/?$/, '/storage');

const normalizeImageUrl = (imageUrl: string | null): string | null => {
  if (!imageUrl) return null;
  if (/^(https?:)?\/\//.test(imageUrl) || imageUrl.startsWith('data:image')) {
    return imageUrl;
  }
  if (imageUrl.startsWith('/storage/')) {
    return `${STORAGE_BASE_URL}${imageUrl.replace('/storage', '')}`;
  }
  if (imageUrl.startsWith('storage/')) {
    return `${STORAGE_BASE_URL}/${imageUrl.replace(/^storage\//, '')}`;
  }
  return imageUrl;
};

const normalizeLessonDetail = (lesson: LessonDetail): LessonDetail => ({
  ...lesson,
  slides: lesson.slides.map(slide => ({
    ...slide,
    image_url: normalizeImageUrl(slide.image_url),
  })),
});

const lessonService = {
  /**
   * Lấy chi tiết bài học (slides + quiz info + progress)
   */
  getLessonDetail: async (lessonId: number): Promise<{success: boolean; data: LessonDetail}> => {
    const response = await apiClient.get(`/student/lessons/${lessonId}`);
    return {
      ...response.data,
      data: normalizeLessonDetail(response.data.data),
    };
  },

  /**
   * Cập nhật tiến trình xem slide
   */
  updateSlideProgress: async (
    lessonId: number,
    slidesViewed: number,
    totalSlides: number,
  ): Promise<{success: boolean; data: any}> => {
    const response = await apiClient.post(`/student/lessons/${lessonId}/slide-progress`, {
      slides_viewed: slidesViewed,
      total_slides: totalSlides,
    });
    return response.data;
  },

  /**
   * Bắt đầu quiz - nhận câu hỏi
   */
  startQuiz: async (quizId: number): Promise<{success: boolean; data: QuizStartData}> => {
    const response = await apiClient.post(`/student/quizzes/${quizId}/start`);
    return response.data;
  },

  /**
   * Nộp bài quiz
   */
  submitQuiz: async (
    quizId: number,
    attemptId: number,
    answers: {question_id: number; option_id: number}[],
  ): Promise<{success: boolean; message: string; data: QuizSubmitResult}> => {
    const response = await apiClient.post(`/student/quizzes/${quizId}/submit`, {
      attempt_id: attemptId,
      answers,
    });
    return response.data;
  },

  /**
   * Lấy kết quả chi tiết của quiz
   */
  getQuizResult: async (attemptId: number): Promise<{success: boolean; data: QuizResultData}> => {
    const response = await apiClient.get(`/student/quizzes/attempts/${attemptId}/result`);
    return response.data;
  },

  /**
   * Hoi AI assistant ve noi dung bai hoc/quiz. Backend khong gui answer key cho AI.
   */
  askAssistant: async (
    lessonId: number,
    message: string,
    conversationHistory: LessonChatMessage[],
  ): Promise<{success: boolean; data: LessonChatResponse}> => {
    const response = await apiClient.post(`/student/lessons/${lessonId}/chat`, {
      message,
      conversation_history: conversationHistory,
    });
    return response.data;
  },
};

export default lessonService;
