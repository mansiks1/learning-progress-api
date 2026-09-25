<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CourseControllerTest extends WebTestCase
{
    public function testListCourses(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $course = new Course();
        $course->setTitle('Course for list test');
        $entityManager->persist($course);
        $entityManager->flush();

        $client->request('GET', '/api/courses');

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode(
            $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertContains([
            'id' => $course->getId(),
            'title' => 'Course for list test',
        ], $data);

        $entityManager->remove($course);
        $entityManager->flush();
    }

    public function testUpdateCourse(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $course = new Course();
        $course->setTitle('Title before update');
        $entityManager->persist($course);
        $entityManager->flush();

        $client->jsonRequest('PATCH', '/api/courses/'.$course->getId(), [
            'title' => 'Title after update',
        ]);

        self::assertResponseIsSuccessful();

        $data = json_decode(
            $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame([
            'id' => $course->getId(),
            'title' => 'Title after update',
        ], $data);
        self::assertSame('Title after update', $course->getTitle());

        $entityManager->remove($course);
        $entityManager->flush();
    }

    public function testDeleteCourse(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $course = new Course();
        $course->setTitle('Course for delete test');
        $entityManager->persist($course);
        $entityManager->flush();

        $courseId = $course->getId();

        $client->request('DELETE', '/api/courses/'.$courseId);

        self::assertResponseIsSuccessful();

        $data = json_decode(
            $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame([
            'message' => 'Course deleted',
        ], $data);
        self::assertNull($entityManager->find(Course::class, $courseId));
    }

    public function testShowMissingCourseReturnsNotFound(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/courses/2147483647');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseFormatSame('json');

        $data = json_decode(
            $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame([
            'error' => 'Course not found',
        ], $data);
    }

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

        self::assertSame('title', $data['violations'][0]['propertyPath']);
        self::assertSame(
            'Title is required',
            $data['violations'][0]['title'],
        );
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
