<?php

namespace App\Services;

use App\Repositories\Contracts\LessonPlanRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonPlanService
{
  public function __construct(
    private readonly LessonPlanRepositoryInterface $lessonPlanRepository,
    private readonly AiServiceClient $aiServiceClient,
  ) {}

  /**
   * Lấy danh sách giáo án của giáo viên
   */
  public function getByTeacher(int $teacherId): array
  {
    try {
      $plans = $this->lessonPlanRepository->findByTeacher($teacherId);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $plans,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get lesson plans failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy danh sách giáo án: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Lấy chi tiết giáo án
   */
  public function getDetail(int $id, int $teacherId): array
  {
    try {
      $plan = $this->lessonPlanRepository->findOrFail($id);

      if ($plan->created_by !== $teacherId) {
        return [
          'status' => 403,
          'data' => [
            'success' => false,
            'message' => 'Bạn không có quyền xem giáo án này',
          ],
        ];
      }

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $plan,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get lesson plan detail failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy chi tiết giáo án: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Tạo giáo án mới
   */
  public function create(array $data, int $teacherId): array
  {
    try {
      DB::beginTransaction();

      $plan = $this->lessonPlanRepository->create([
        'title' => $data['title'],
        'subject' => $data['subject'] ?? null,
        'grade_level' => $data['grade_level'] ?? null,
        'content' => $data['content'] ?? null,
        'status' => 'draft',
        'created_by' => $teacherId,
      ]);

      DB::commit();

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'data' => $plan,
          'message' => 'Tạo giáo án thành công',
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Create lesson plan failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi tạo giáo án: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Cập nhật giáo án (lưu nội dung từ editor)
   */
  public function update(int $id, array $data, int $teacherId): array
  {
    try {
      $plan = $this->lessonPlanRepository->findOrFail($id);

      if ($plan->created_by !== $teacherId) {
        return [
          'status' => 403,
          'data' => [
            'success' => false,
            'message' => 'Bạn không có quyền chỉnh sửa giáo án này',
          ],
        ];
      }

      $plan = $this->lessonPlanRepository->update($id, $data);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $plan,
          'message' => 'Cập nhật giáo án thành công',
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Update lesson plan failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi cập nhật giáo án: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Xóa giáo án
   */
  public function delete(int $id, int $teacherId): array
  {
    try {
      $plan = $this->lessonPlanRepository->findOrFail($id);

      if ($plan->created_by !== $teacherId) {
        return [
          'status' => 403,
          'data' => [
            'success' => false,
            'message' => 'Bạn không có quyền xóa giáo án này',
          ],
        ];
      }

      // Xóa chunks trong vector DB nếu có
      try {
        $this->aiServiceClient->deleteDocumentChunks($id);
      } catch (\Exception $e) {
        Log::warning('Failed to delete lesson plan chunks from vector DB', [
          'lesson_plan_id' => $id,
          'error' => $e->getMessage(),
        ]);
      }

      $this->lessonPlanRepository->delete($id);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Xóa giáo án thành công',
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Delete lesson plan failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi xóa giáo án: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Upload tài liệu tham khảo → index vào vector DB để dùng cho autocomplete
   */
  public function uploadReference(int $id, int $teacherId, $file): array
  {
    try {
      $plan = $this->lessonPlanRepository->findOrFail($id);

      if ($plan->created_by !== $teacherId) {
        return [
          'status' => 403,
          'data' => [
            'success' => false,
            'message' => 'Bạn không có quyền thao tác giáo án này',
          ],
        ];
      }

      $path = $file->store('lesson_plan_references', 'local');
      $fileName = $file->getClientOriginalName();

      // Gửi file tới Python AI service để chunk + embed
      $result = $this->aiServiceClient->processDocumentFile($id, $path, $fileName);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Upload và xử lý tài liệu tham khảo thành công',
          'chunks_count' => $result['chunks_count'] ?? 0,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Upload reference failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi upload tài liệu: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Upload tài liệu dạng text → index vào vector DB
   */
  public function uploadReferenceText(int $id, int $teacherId, string $text): array
  {
    try {
      $plan = $this->lessonPlanRepository->findOrFail($id);

      if ($plan->created_by !== $teacherId) {
        return [
          'status' => 403,
          'data' => [
            'success' => false,
            'message' => 'Bạn không có quyền thao tác giáo án này',
          ],
        ];
      }

      $result = $this->aiServiceClient->processDocumentText($id, $text);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Xử lý nội dung tham khảo thành công',
          'chunks_count' => $result['chunks_count'] ?? 0,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Upload reference text failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi xử lý nội dung: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Gọi AI autocomplete để gợi ý nội dung tiếp theo
   */
  public function aiSuggest(int $lessonPlanId, string $text, int $teacherId): array
  {
    try {
      $plan = $this->lessonPlanRepository->findOrFail($lessonPlanId);

      if ($plan->created_by !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['suggestion' => ''],
        ];
      }

      return $this->aiServiceClient->getAutocompleteSuggestion($text, $lessonPlanId);
    } catch (\Exception $e) {
      Log::warning('AI suggest failed', ['error' => $e->getMessage()]);
      return ['suggestion' => ''];
    }
  }

  /**
   * Tìm kiếm giáo án
   */
  public function search(int $teacherId, string $query): array
  {
    try {
      $plans = $this->lessonPlanRepository->searchByTeacher($teacherId, $query);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $plans,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Search lesson plans failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi tìm kiếm giáo án: ' . $e->getMessage(),
        ],
      ];
    }
  }
}
