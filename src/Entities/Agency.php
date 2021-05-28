<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

/**
 * @ORM\Entity()
 * @ORM\Table(name="agencies")
 */
class Agency
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $externalId;

    /**
     * @var Region
     * @ORM\ManyToOne(targetEntity=Region::class)
     */
    private $region;

    public static function create(Region $region, string $name, int $externalId): self
    {
        $self = new self();
        $self->name = $name;
        $self->externalId = $externalId;

        return $self;
    }

    public function getExternalId(): int
    {
        return $this->externalId;
    }
}