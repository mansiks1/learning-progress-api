<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateCourseInput;
use App\Dto\UpdateCourseInput;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CourseService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(CreateCourseInput $input): Course
    {
        $course = new Course();
        $course->setTitle(trim($input->title));

        $this->entityManager->persist($course);
        $this->entityManager->flush();

        return $course;
    }

    public function update(
        Course $course,
        UpdateCourseInput $input,
    ): Course {
        $course->setTitle(trim($input->title));
        $this->entityManager->flush();

        return $course;
    }

    public function delete(Course $course): bool
    {
        if (!$course->getLessons()->isEmpty()) {
            return false;
        }

        $this->entityManager->remove($course);
        $this->entityManager->flush();

        return true;
    }
}
