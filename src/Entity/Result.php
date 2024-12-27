<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

#[ORM\Entity, ORM\Table(name: 'results')]
class Result
{
    public final const RESULT_ATTR = 'result';
    #[ORM\Column(
        name: 'id',
        type: 'integer',
        nullable: false
    )]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\XmlAttribute]
    private ?int $id = 0;

    #[ORM\Column(
        name: 'score',
        type: 'integer',
        nullable: false
    )]
    private int $score;

    #[ORM\Column(
        name: 'date',
        type: 'datetime',
        nullable: false
    )]
    private \DateTime $date;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    public function __construct(User $user, int $score, \DateTime $date)
    {
        $this->user = $user;
        $this->score = $score;
        $this->date = $date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
