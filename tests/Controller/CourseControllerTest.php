<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CourseControllerTest extends WebTestCase
{
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
