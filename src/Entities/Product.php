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
     * @ORM\Column(name="external_id", type="integer")
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
    private $link;

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
     * @param string|null $link
     * @return Product
     */
    public static function create(
        Agency $agency,
        string $name,
        int $externalId,
        int $balance,
        string $vendor = null,
        string $link = null
    ): self {
        $product = new self();
        $product->name = $name;
        $product->externalId = $externalId;
        $product->vendor = $vendor;
        $product->link = $link;
        $product->balance = $balance;
        $product->agency = $agency;
        return $product;
    }

    public function setBalance(int $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * @return int
     */
    public function getExternalId(): int
    {
        return $this->externalId;
    }
}