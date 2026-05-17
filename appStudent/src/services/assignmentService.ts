import apiClient from './apiClient';

// ==========================================
// Types
// ==========================================

export interface AssignmentFileInfo {
  id: number;
  file_path: string;
  file_name: string;
  file_size: number;
  mime_type: string;
}

export interface SubmissionAttachmentInfo {
  id: number;
  file_path: string;
  file_name: string;
  file_size: number;
  mime_type: string;
  file_type: string; // 'image' | 'document' | 'other'
  uploaded_at: string;
}

export interface GradingInfo {
  id: number;
  submission_id: number;
  score: number | null;
  max_score: number;
  feedback: string | null;
  graded_by: number | null;
  graded_at: string | null;
  ai_suggested_score: number | null;
  ai_feedback: string | null;
  ai_status: string | null; // 'pending' | 'processing' | 'completed' | 'failed'
  ai_graded_at: string | null;
}

export interface SubmissionInfo {
  id: number;
  assignment_id: number;
  student_id: number;
  submitted_at: string;
  is_late: boolean;
  status: string;
  attachments: SubmissionAttachmentInfo[];
  grading: GradingInfo | null;
}

export interface AssignmentInfo {
  id: number;
  class_id: number;
  title: string;
  description: string | null;
  instructions: string | null;
  due_date: string | null;
  max_score: number;
  submission_type: string; // 'file' | 'text' | 'both'
  status: string;
  allow_late_submission: boolean;
  late_penalty: number;
  created_by: number;
  created_at: string;
  updated_at: string;
  files: AssignmentFileInfo[];
  submissions: SubmissionInfo[];
}

// ==========================================
// Service
// ==========================================

const assignmentService = {
  /**
   * Lấy danh sách bài tập theo lớp (student)
   */
  getAssignmentsByClass: async (
    classId: number,
  ): Promise<{success: boolean; data: AssignmentInfo[]}> => {
    const response = await apiClient.get(`/student/assignments/class/${classId}`);
    return response.data;
  },

  /**
   * Nộp bài tập (student)
   */
  submitAssignment: async (
    assignmentId: number,
    files: {uri: string; name: string; type: string}[],
    textContent?: string,
  ): Promise<{success: boolean; message: string; data: SubmissionInfo}> => {
    const formData = new FormData();

    files.forEach((file, index) => {
      formData.append(`files[${index}]`, {
        uri: file.uri,
        name: file.name,
        type: file.type,
      } as any);
    });

    if (textContent) {
      formData.append('text_content', textContent);
    }

    const response = await apiClient.post(
      `/student/assignments/${assignmentId}/submit`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        timeout: 60000, // 60s cho file upload
      },
    );
    return response.data;
  },
};

export default assignmentService;
