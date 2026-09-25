<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateCourseInput;
use App\Dto\UpdateCourseInput;
use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Service\CourseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class CourseController
{
    //преобразование массива в json
    #[Route('/api/courses', name: 'api_courses', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): JsonResponse
    {
        $courses = $courseRepository->findAll();

        $result = [];

        foreach ($courses as $course) {
            $result[] = $this->courseToArray($course);
        }

        return new JsonResponse($result);
    }

    //добавление нового курса
    #[Route('/api/courses', name: 'api_course_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateCourseInput $input,
        CourseService $courseService,
    ): JsonResponse {
        $course = $courseService->create($input);

        return new JsonResponse(
            $this->courseToArray($course),
            JsonResponse::HTTP_CREATED,
        );
    }

    #[Route('/api/courses/{id}', name: 'api_course_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        CourseRepository $courseRepository,
        CourseService $courseService,
    ): JsonResponse {
        $course = $courseRepository->find($id);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        if (!$courseService->delete($course)) {
            return new JsonResponse(
                ['error' => 'Course with lessons cannot be deleted'],
                JsonResponse::HTTP_CONFLICT,
            );
        }

        return new JsonResponse([
            'message' => 'Course deleted',
        ]);
    }

    //получение курса по id
    #[Route('/api/courses/{id}', name: 'api_course_show', methods: ['GET'])]
    public function show(
        int $id,
        CourseRepository $courseRepository,
    ): JsonResponse {
        $course = $courseRepository->find($id);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            $this->courseToArray($course),
        );
    }

    #[Route('/api/courses/{id}', name: 'api_course_update', methods: ['PATCH'])]
    public function update(
        int $id,
        #[MapRequestPayload] UpdateCourseInput $input,
        CourseRepository $courseRepository,
        CourseService $courseService,
    ): JsonResponse {
        $course = $courseRepository->find($id);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $course = $courseService->update($course, $input);

        return new JsonResponse(
            $this->courseToArray($course),
        );
    }

    private function courseToArray(Course $course): array
    {
        return [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
        ];
    }
}
