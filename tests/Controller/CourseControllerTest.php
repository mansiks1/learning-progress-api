<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CourseControllerTest extends WebTestCase
{
    public function testShowCourse(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $course = new Course();
        $course->setTitle('Course for GET test');

        $entityManager->persist($course);
        $entityManager->flush();

        $courseId = $course->getId();

        $client->request('GET', '/api/courses/'.$courseId);

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode(
            $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame([
            'id' => $courseId,
            'title' => 'Course for GET test',
        ], $data);

        $entityManager->remove($course);
        $entityManager->flush();
    }

    public function testRejectsBlankTitle(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/courses', [
            'title' => '   ',
        ]);

        self::assertResponseStatusCodeSame(422);

        $data = json_decode(
            $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame([
            'errors' => ['Title is required'],
        ], $data);
    }

    public function testCreateCourse(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/courses', [
            'title' => 'PHPUnit Course',
        ]);

        self::assertResponseStatusCodeSame(201);

        $data = json_decode(
            $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($data);
        self::assertIsInt($data['id']);
        self::assertSame('PHPUnit Course', $data['title']);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $course = $entityManager->find(Course::class, $data['id']);

        self::assertInstanceOf(Course::class, $course);
        self::assertSame('PHPUnit Course', $course->getTitle());

        $entityManager->remove($course);
        $entityManager->flush();
    }
}
