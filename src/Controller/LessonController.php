<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateLessonInput;
use App\Entity\Lesson;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
        #[MapRequestPayload] CreateLessonInput $input,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $course = $courseRepository->find($courseId);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $lesson = new Lesson();
        $lesson->setTitle(trim($input->title));
        $lesson->setPosition($input->position);
        $lesson->setCourse($course);

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

    #[Route(
        '/api/lessons/{id}',
        name: 'api_lesson_update',
        methods: ['PATCH'],
    )]
    public function update(
        int $id,
        Request $request,
        LessonRepository $lessonRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $lesson = $lessonRepository->find($id);

        if ($lesson === null) {
            return new JsonResponse(
                ['error' => 'Lesson not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $data = $request->toArray();

        if (!array_key_exists('title', $data)
            && !array_key_exists('position', $data)
        ) {
            return new JsonResponse(
                ['errors' => ['At least one field is required']],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (array_key_exists('title', $data)) {
            if (!is_string($data['title'])) {
                return new JsonResponse(
                    ['errors' => ['Title must be a string']],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $lesson->setTitle(trim($data['title']));
        }

        if (array_key_exists('position', $data)) {
            if (!is_int($data['position'])) {
                return new JsonResponse(
                    ['errors' => ['Position must be an integer']],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $lesson->setPosition($data['position']);
        }

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

        $entityManager->flush();

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
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $lesson = $lessonRepository->find($id);

        if ($lesson === null) {
            return new JsonResponse(
                ['error' => 'Lesson not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $entityManager->remove($lesson);
        $entityManager->flush();

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
