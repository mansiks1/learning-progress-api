<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Course;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LessonControllerTest extends WebTestCase
{
    public function testLessonLifecycle(): void
    {
        $client = static::createClient();
        $courseId = $this->createCourse();

        try {
            $client->jsonRequest('POST', "/api/courses/$courseId/lessons", [
                'title' => 'Second lesson',
                'position' => 2,
            ]);
            self::assertResponseStatusCodeSame(201);
            $secondLesson = $this->decodeJson($client->getResponse()->getContent());

            $client->jsonRequest('POST', "/api/courses/$courseId/lessons", [
                'title' => 'First lesson',
                'position' => 1,
            ]);
            self::assertResponseStatusCodeSame(201);
            $firstLesson = $this->decodeJson($client->getResponse()->getContent());

            $client->request('GET', "/api/courses/$courseId/lessons");
            self::assertResponseIsSuccessful();
            $lessons = $this->decodeJson($client->getResponse()->getContent());
            self::assertSame([1, 2], array_column($lessons, 'position'));

            $firstLessonId = $firstLesson['id'];
            $client->request('GET', "/api/lessons/$firstLessonId");
            self::assertResponseIsSuccessful();
            self::assertSame(
                'First lesson',
                $this->decodeJson($client->getResponse()->getContent())['title'],
            );

            $client->jsonRequest('PATCH', "/api/lessons/$firstLessonId", [
                'title' => 'Updated first lesson',
            ]);
            self::assertResponseIsSuccessful();
            self::assertSame(
                'Updated first lesson',
                $this->decodeJson($client->getResponse()->getContent())['title'],
            );

            $client->request('DELETE', "/api/lessons/$firstLessonId");
            self::assertResponseIsSuccessful();
            self::assertSame(
                ['message' => 'Lesson deleted'],
                $this->decodeJson($client->getResponse()->getContent()),
            );

            $secondLessonId = $secondLesson['id'];
            $client->request('DELETE', "/api/lessons/$secondLessonId");
            self::assertResponseIsSuccessful();

            $client->request('GET', "/api/lessons/$firstLessonId");
            self::assertResponseStatusCodeSame(404);
        } finally {
            $this->deleteCourse($courseId);
        }
    }

    public function testRejectsInvalidLessonData(): void
    {
        $client = static::createClient();
        $courseId = $this->createCourse();

        try {
            $client->jsonRequest('POST', "/api/courses/$courseId/lessons", [
                'title' => 'Invalid lesson',
                'position' => 0,
            ]);

            self::assertResponseStatusCodeSame(422);
            self::assertSame(
                ['errors' => ['Position must be greater than zero']],
                $this->decodeJson($client->getResponse()->getContent()),
            );
        } finally {
            $this->deleteCourse($courseId);
        }
    }

    public function testReturnsNotFoundForMissingLesson(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/lessons/2147483647');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            ['error' => 'Lesson not found'],
            $this->decodeJson($client->getResponse()->getContent()),
        );
    }

    private function createCourse(): int
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $course = new Course();
        $course->setTitle('Course for lesson test');
        $entityManager->persist($course);
        $entityManager->flush();

        return $course->getId();
    }

    private function deleteCourse(int $courseId): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $course = $entityManager->find(Course::class, $courseId);

        if ($course !== null) {
            $lessonRepository = static::getContainer()->get(LessonRepository::class);

            foreach ($lessonRepository->findBy(['course' => $course]) as $lesson) {
                $entityManager->remove($lesson);
            }

            $entityManager->remove($course);
            $entityManager->flush();
        }
    }

    private function decodeJson(string $content): array
    {
        return json_decode(
            $content,
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
