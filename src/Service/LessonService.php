<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateLessonInput;
use App\Dto\UpdateLessonInput;
use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LessonService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(
        Course $course,
        CreateLessonInput $input,
    ): Lesson {
        $lesson = new Lesson();
        $lesson->setTitle(trim($input->title));
        $lesson->setPosition($input->position);
        $lesson->setCourse($course);

        $this->entityManager->persist($lesson);
        $this->entityManager->flush();

        return $lesson;
    }

    public function update(
        Lesson $lesson,
        UpdateLessonInput $input,
    ): Lesson {
        if ($input->title !== null) {
            $lesson->setTitle(trim($input->title));
        }

        if ($input->position !== null) {
            $lesson->setPosition($input->position);
        }

        $this->entityManager->flush();

        return $lesson;
    }
}
