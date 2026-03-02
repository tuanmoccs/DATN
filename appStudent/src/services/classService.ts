import apiClient from './apiClient';

export interface TeacherInfo {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
}

export interface LessonInfo {
  id: number;
  class_id: number;
  title: string;
  description: string | null;
  order: number;
  status: string;
  created_at: string;
}

export interface StudentInfo {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
}

export interface EnrollmentWithUser {
  id: number;
  class_id: number;
  user_id: number;
  status: string;
  joined_at: string | null;
  user: StudentInfo;
}

export interface ClassInfo {
  id: number;
  code: string;
  name: string;
  description: string | null;
  teacher_id: number;
  semester: string | null;
  max_students: number | null;
  status: string;
  created_at: string;
  teacher?: TeacherInfo;
  student_count?: number;
  lesson_count?: number;
  enrollment_status?: string;
  enrollment_id?: number;
  lessons?: LessonInfo[];
  enrollment?: EnrollmentWithUser[];
}

export interface JoinClassResponse {
  success: boolean;
  message: string;
  data?: any;
}

export interface ClassListResponse {
  success: boolean;
  data: ClassInfo[];
}

export interface ClassDetailResponse {
  success: boolean;
  data: ClassInfo;
}

const classService = {
  /**
   * Học sinh gửi yêu cầu tham gia lớp bằng mã code
   */
  joinClass: async (code: string): Promise<JoinClassResponse> => {
    const response = await apiClient.post('/student/classes/join', {
      code: code.toUpperCase(),
    });
    return response.data;
  },

  /**
   * Lấy danh sách lớp đã tham gia / đang chờ duyệt
   */
  getMyClasses: async (): Promise<ClassListResponse> => {
    const response = await apiClient.get('/student/classes');
    return response.data;
  },

  /**
   * Lấy chi tiết lớp học (bài học, giáo viên, danh sách học sinh)
   */
  getClassDetail: async (classId: number): Promise<ClassDetailResponse> => {
    const response = await apiClient.get(`/student/classes/${classId}`);
    return response.data;
  },
};

export default classService;
