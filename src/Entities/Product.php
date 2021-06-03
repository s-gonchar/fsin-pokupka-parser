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
     * @var int
     * @ORM\Column(type="integer")
     */
    private $balance;

    /**
     * @var Agency
     * @ORM\ManyToOne(targetEntity=Agency::class)
     */
    private $agency;

    /**
     * Product constructor.
     * @param Agency $agency
     * @param string $name
     * @param int $externalId
     * @param int $balance
     * @param string|null $vendor
     * @param string|null $supplier
     * @return Product
     */
    public static function create(
        Agency $agency,
        string $name,
        int $externalId,
        int $balance,
        string $vendor = null,
        string $supplier = null
    ): self {
        $product = new self();
        $product->name = $name;
        $product->externalId = $externalId;
        $product->vendor = $vendor;
        $product->supplier = $supplier;
        $product->balance = $balance;
        $product->agency = $agency;
        return $product;
    }

    public function setBalance(int $balance): self
    {
        $this->balance = $balance;
        return $this;
    }
}