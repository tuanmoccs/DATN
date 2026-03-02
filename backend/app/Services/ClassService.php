<?php

namespace App\Services;

use App\Repositories\Contracts\ClassRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Support\Str;

class ClassService
{
  public function __construct(
    private readonly ClassRepositoryInterface $classRepository,
    private readonly EnrollmentRepositoryInterface $enrollmentRepository
  ) {}

  /**
   * Lấy danh sách lớp học của giáo viên
   */
  public function getClassesByTeacher(int $teacherId): array
  {
    try {
      $classes = $this->classRepository->findByTeacher($teacherId);

      $classes->each(function ($class) {
        $class->student_count = $class->enrollment()->where('status', 'active')->count();
        $class->pending_count = $class->enrollment()->where('status', 'pending')->count();
      });

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $classes,
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy danh sách lớp học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Tạo lớp học mới với mã tự sinh
   */
  public function createClass(array $data, int $teacherId): array
  {
    try {
      $code = $this->generateUniqueCode();

      $classData = [
        'code' => $code,
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'teacher_id' => $teacherId,
        'semester' => $data['semester'] ?? null,
        'max_students' => $data['max_students'] ?? null,
        'status' => $data['status'] ?? 'active',
      ];

      $class = $this->classRepository->create($classData);

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'Tạo lớp học thành công',
          'data' => $class,
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi tạo lớp học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Cập nhật thông tin lớp học
   */
  public function updateClass(int $classId, array $data, int $teacherId): array
  {
    try {
      $class = $this->classRepository->find($classId);

      if (!$class) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy lớp học'],
        ];
      }

      if ($class->teacher_id !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa lớp học này'],
        ];
      }

      $updateData = array_filter([
        'name' => $data['name'] ?? null,
        'description' => $data['description'] ?? null,
        'semester' => $data['semester'] ?? null,
        'max_students' => $data['max_students'] ?? null,
        'status' => $data['status'] ?? null,
      ], fn($v) => $v !== null);

      $class = $this->classRepository->update($classId, $updateData);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Cập nhật lớp học thành công',
          'data' => $class,
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi cập nhật lớp học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Xóa lớp học (soft delete)
   */
  public function deleteClass(int $classId, int $teacherId): array
  {
    try {
      $class = $this->classRepository->find($classId);

      if (!$class) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy lớp học'],
        ];
      }

      if ($class->teacher_id !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền xóa lớp học này'],
        ];
      }

      $this->classRepository->delete($classId);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Xóa lớp học thành công',
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi xóa lớp học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Lấy chi tiết lớp học kèm danh sách sinh viên
   */
  public function getClassDetail(int $classId, int $teacherId): array
  {
    try {
      $class = $this->classRepository->getClassWithRelations($classId, ['teacher', 'enrollment.user']);

      if (!$class) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy lớp học'],
        ];
      }

      if ($class->teacher_id !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền xem lớp học này'],
        ];
      }

      $class->student_count = $class->enrollment->where('status', 'active')->count();
      $class->pending_count = $class->enrollment->where('status', 'pending')->count();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $class,
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy chi tiết lớp học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Học sinh yêu cầu tham gia lớp bằng mã code
   */
  public function requestJoinClass(string $code, int $studentId): array
  {
    try {
      $class = $this->classRepository->findByCode($code);

      if (!$class) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Mã lớp học không hợp lệ'],
        ];
      }

      if ($class->status !== 'active') {
        return [
          'status' => 400,
          'data' => ['success' => false, 'message' => 'Lớp học hiện không hoạt động'],
        ];
      }

      // Kiểm tra đã gửi yêu cầu hoặc đã tham gia
      $existing = $this->enrollmentRepository->findByClassAndUser($class->id, $studentId);
      if ($existing) {
        $statusMessages = [
          'active' => 'Bạn đã là thành viên của lớp này',
          'pending' => 'Bạn đã gửi yêu cầu tham gia, vui lòng chờ giáo viên duyệt',
          'rejected' => 'Yêu cầu của bạn đã bị từ chối trước đó',
        ];
        $msg = $statusMessages[$existing->status] ?? 'Bạn đã có yêu cầu tham gia lớp này';

        return [
          'status' => 400,
          'data' => ['success' => false, 'message' => $msg],
        ];
      }

      // Kiểm tra sĩ số tối đa
      if ($class->max_students) {
        $currentCount = $class->enrollment()->where('status', 'active')->count();
        if ($currentCount >= $class->max_students) {
          return [
            'status' => 400,
            'data' => ['success' => false, 'message' => 'Lớp học đã đầy'],
          ];
        }
      }

      $enrollment = $this->enrollmentRepository->create([
        'class_id' => $class->id,
        'user_id' => $studentId,
        'status' => 'pending',
      ]);

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'Gửi yêu cầu tham gia thành công. Vui lòng chờ giáo viên duyệt.',
          'data' => $enrollment,
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi gửi yêu cầu: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Giáo viên duyệt yêu cầu tham gia
   */
  public function approveEnrollment(int $enrollmentId, int $teacherId): array
  {
    try {
      $enrollment = $this->enrollmentRepository->find($enrollmentId);

      if (!$enrollment) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy yêu cầu'],
        ];
      }

      $class = $this->classRepository->find($enrollment->class_id);
      if ($class->teacher_id !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền duyệt yêu cầu này'],
        ];
      }

      if ($enrollment->status !== 'pending') {
        return [
          'status' => 400,
          'data' => ['success' => false, 'message' => 'Yêu cầu này đã được xử lý'],
        ];
      }

      // Kiểm tra sĩ số
      if ($class->max_students) {
        $currentCount = $class->enrollment()->where('status', 'active')->count();
        if ($currentCount >= $class->max_students) {
          return [
            'status' => 400,
            'data' => ['success' => false, 'message' => 'Lớp học đã đầy, không thể duyệt thêm'],
          ];
        }
      }

      $this->enrollmentRepository->update($enrollmentId, [
        'status' => 'active',
        'joined_at' => now(),
      ]);

      $enrollment = $this->enrollmentRepository->find($enrollmentId);
      $enrollment->load('user');

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Đã duyệt học sinh vào lớp',
          'data' => $enrollment,
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi duyệt yêu cầu: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Giáo viên từ chối yêu cầu tham gia
   */
  public function rejectEnrollment(int $enrollmentId, int $teacherId): array
  {
    try {
      $enrollment = $this->enrollmentRepository->find($enrollmentId);

      if (!$enrollment) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy yêu cầu'],
        ];
      }

      $class = $this->classRepository->find($enrollment->class_id);
      if ($class->teacher_id !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền từ chối yêu cầu này'],
        ];
      }

      if ($enrollment->status !== 'pending') {
        return [
          'status' => 400,
          'data' => ['success' => false, 'message' => 'Yêu cầu này đã được xử lý'],
        ];
      }

      $this->enrollmentRepository->update($enrollmentId, ['status' => 'rejected']);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Đã từ chối yêu cầu tham gia',
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi từ chối yêu cầu: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Giáo viên xóa học sinh khỏi lớp
   */
  public function removeStudent(int $enrollmentId, int $teacherId): array
  {
    try {
      $enrollment = $this->enrollmentRepository->find($enrollmentId);

      if (!$enrollment) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy học sinh'],
        ];
      }

      $class = $this->classRepository->find($enrollment->class_id);
      if ($class->teacher_id !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền thực hiện'],
        ];
      }

      $this->enrollmentRepository->update($enrollmentId, ['status' => 'dropped']);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Đã xóa học sinh khỏi lớp',
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Sinh mã lớp học duy nhất (6 ký tự chữ + số)
   */
  private function generateUniqueCode(): string
  {
    do {
      $code = strtoupper(Str::random(6));
    } while ($this->classRepository->findByCode($code));

    return $code;
  }

  /**
   * Lấy danh sách lớp học của học sinh (đã tham gia + đang chờ)
   */
  public function getStudentClasses(int $studentId): array
  {
    try {
      $enrollments = $this->enrollmentRepository->query()
        ->where('user_id', $studentId)
        ->whereIn('status', ['active', 'pending'])
        ->with(['class.teacher', 'class.lessons'])
        ->orderBy('created_at', 'desc')
        ->get();

      $data = $enrollments->map(function ($enrollment) {
        $class = $enrollment->class;
        if ($class) {
          $class->enrollment_status = $enrollment->status;
          $class->enrollment_id = $enrollment->id;
          $class->student_count = $class->enrollment()->where('status', 'active')->count();
          $class->lesson_count = $class->lessons()->count();
        }
        return $class;
      })->filter();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $data->values(),
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy danh sách lớp: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Lấy chi tiết lớp học cho học sinh
   */
  public function getStudentClassDetail(int $classId, int $studentId): array
  {
    try {
      $enrollment = $this->enrollmentRepository->findByClassAndUser($classId, $studentId);

      if (!$enrollment || !in_array($enrollment->status, ['active', 'pending'])) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền xem lớp học này'],
        ];
      }

      $class = $this->classRepository->getClassWithRelations($classId, [
        'teacher',
        'lessons',
        'enrollment' => function ($q) {
          $q->where('status', 'active')->with('user');
        },
      ]);

      if (!$class) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy lớp học'],
        ];
      }

      $class->enrollment_status = $enrollment->status;
      $class->student_count = $class->enrollment->count();
      $class->lesson_count = $class->lessons->count();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $class,
        ],
      ];
    } catch (\Exception $e) {
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy chi tiết lớp: ' . $e->getMessage(),
        ],
      ];
    }
  }
}
