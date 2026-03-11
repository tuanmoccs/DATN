<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
  public function __construct(
    private readonly DashboardService $dashboardService
  ) {}

  /**
   * Get teacher dashboard data
   */
  public function teacherDashboard(): JsonResponse
  {
    $result = $this->dashboardService->getTeacherDashboard(auth()->id());
    return response()->json($result['data'], $result['status']);
  }
}
