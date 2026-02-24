<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Contracts
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\OtpRepositoryInterface;
use App\Repositories\Contracts\ClassRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\LessonRepositoryInterface;
use App\Repositories\Contracts\QuizRepositoryInterface;
use App\Repositories\Contracts\PresentationRepositoryInterface;

// Repository Implementations
use App\Repositories\UserRepository;
use App\Repositories\OtpRepository;
use App\Repositories\ClassRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\LessonRepository;
use App\Repositories\QuizRepository;
use App\Repositories\PresentationRepository;

class RepositoryServiceProvider extends ServiceProvider
{
  /**
   * All repository bindings.
   */
  protected array $repositories = [
    UserRepositoryInterface::class => UserRepository::class,
    OtpRepositoryInterface::class => OtpRepository::class,
    ClassRepositoryInterface::class => ClassRepository::class,
    EnrollmentRepositoryInterface::class => EnrollmentRepository::class,
    LessonRepositoryInterface::class => LessonRepository::class,
    QuizRepositoryInterface::class => QuizRepository::class,
    PresentationRepositoryInterface::class => PresentationRepository::class,
  ];

  /**
   * Register repository bindings.
   */
  public function register(): void
  {
    foreach ($this->repositories as $interface => $implementation) {
      $this->app->bind($interface, $implementation);
    }
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    //
  }
}
