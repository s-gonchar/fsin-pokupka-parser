<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="regions")
 */
class Region
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
     * @ORM\Column(name="external_id", type="integer")
     */
    private $externalId;

    public static function create($name, $externalId): self
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