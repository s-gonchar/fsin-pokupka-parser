<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="products")
 */
class Product
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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $vendor;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $supplier;

    /**
     * @var Agency
     * @ORM\ManyToOne(targetEntity=Agency::class)
     */
    private $agency;
}