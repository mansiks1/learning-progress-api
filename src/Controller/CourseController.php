<?php

declare(strict_types=1);
namespace App\Controller;

use App\Dto\CreateCourseInput;
use App\Service\CourseService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CourseRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $course = $courseRepository->find($id);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $entityManager->remove($course);
        $entityManager->flush();

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
        Request $request,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $course = $courseRepository->find($id);

        if ($course === null) {
            return new JsonResponse(
                ['error' => 'Course not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $data = $request->toArray();
        $title = $data['title'] ?? null;

        if (!is_string($title)) {
            return new JsonResponse(
                ['errors' => ['Title must be a string']],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $course->setTitle(trim($title));

        $violations = $validator->validate($course);

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
