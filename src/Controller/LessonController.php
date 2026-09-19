<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Lesson;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        Request $request,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $course = $courseRepository->find($courseId);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $data = $request->toArray();

        $title = $data['title'] ?? null;
        $position = $data['position'] ?? null;

        if (!is_string($title)) {
            return new JsonResponse(
                ['errors' => ['Title must be a string']],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (!is_int($position)) {
            return new JsonResponse(
                ['errors' => ['Position must be an integer']],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $lesson = new Lesson();
        $lesson->setTitle(trim($title));
        $lesson->setPosition($position);
        $lesson->setCourse($course);

        $violations = $validator->validate($lesson);

        if (count($violations) > 0) {
            $errors = [];

            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return new JsonResponse(
                ['errors' => $errors],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $entityManager->persist($lesson);
        $entityManager->flush();

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
