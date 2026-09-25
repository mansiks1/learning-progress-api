<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateLessonInput;
use App\Dto\UpdateLessonInput;
use App\Entity\Lesson;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\LessonService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class LessonController
{
    #[Route(
        '/api/courses/{courseId}/lessons',
        name: 'api_lesson_create',
        methods: ['POST'],
    )]
    public function create(
        int $courseId,
        #[MapRequestPayload] CreateLessonInput $input,
        CourseRepository $courseRepository,
        LessonService $lessonService,
    ): JsonResponse {
        $course = $courseRepository->find($courseId);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $lesson = $lessonService->create($course, $input);

        return new JsonResponse(
            $this->lessonToArray($lesson),
            JsonResponse::HTTP_CREATED,
        );
    }

    #[Route(
        '/api/courses/{courseId}/lessons',
        name: 'api_lesson_index',
        methods: ['GET'],
    )]
    public function index(
        int $courseId,
        CourseRepository $courseRepository,
        LessonRepository $lessonRepository,
    ): JsonResponse {
        $course = $courseRepository->find($courseId);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $lessons = $lessonRepository->findBy(
            ['course' => $course],
            ['position' => 'ASC'],
        );

        $result = [];

        foreach ($lessons as $lesson) {
            $result[] = $this->lessonToArray($lesson);
        }

        return new JsonResponse($result);
    }

    #[Route(
        '/api/lessons/{id}',
        name: 'api_lesson_show',
        methods: ['GET'],
    )]
    public function show(
        int $id,
        LessonRepository $lessonRepository,
    ): JsonResponse {
        $lesson = $lessonRepository->find($id);

        if ($lesson === null) {
            return new JsonResponse(
                ['error' => 'Lesson not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            $this->lessonToArray($lesson),
        );
    }

    #[Route(
        '/api/lessons/{id}',
        name: 'api_lesson_update',
        methods: ['PATCH'],
    )]
    public function update(
        int $id,
        #[MapRequestPayload] UpdateLessonInput $input,
        LessonRepository $lessonRepository,
        LessonService $lessonService,
    ): JsonResponse {
        $lesson = $lessonRepository->find($id);

        if ($lesson === null) {
            return new JsonResponse(
                ['error' => 'Lesson not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $lesson = $lessonService->update($lesson, $input);

        return new JsonResponse(
            $this->lessonToArray($lesson),
        );
    }

    #[Route(
        '/api/lessons/{id}',
        name: 'api_lesson_delete',
        methods: ['DELETE'],
    )]
    public function delete(
        int $id,
        LessonRepository $lessonRepository,
        LessonService $lessonService,
    ): JsonResponse {
        $lesson = $lessonRepository->find($id);

        if ($lesson === null) {
            return new JsonResponse(
                ['error' => 'Lesson not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $lessonService->delete($lesson);

        return new JsonResponse([
            'message' => 'Lesson deleted',
        ]);
    }

    private function lessonToArray(Lesson $lesson): array
    {
        return [
            'id' => $lesson->getId(),
            'title' => $lesson->getTitle(),
            'position' => $lesson->getPosition(),
            'courseId' => $lesson->getCourse()?->getId(),
        ];
    }
}
