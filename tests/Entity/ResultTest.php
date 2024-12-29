<?php
namespace App\Tests\Entity;

use App\Entity\Result;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testResultEntity(): void
    {
        $user = $this->createMock(User::class);

        $score = 100;
        $date = new \DateTime('2024-12-30 10:00:00');
        $result = new Result($user, $score, $date);

        $this->assertNull($result->getId(), 'Result ID should initially be null');
        $this->assertSame($score, $result->getScore(), 'Score is not correctly set');
        $this->assertSame($date, $result->getDate(), 'Date is not correctly set');
        $this->assertSame($user, $result->getUser(), 'User is not correctly set');

        $newScore = 200;
        $newDate = new \DateTime('2024-12-31 12:00:00');
        $result->setScore($newScore);
        $result->setDate($newDate);

        $this->assertSame($newScore, $result->getScore(), 'Updated score is not correct');
        $this->assertSame($newDate, $result->getDate(), 'Updated date is not correct');

        $this->assertInstanceOf(User::class, $result->getUser(), 'User is not of expected type');
    }
}
