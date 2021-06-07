<?php

namespace Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Entities\Product;

class ProductRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->repo = $entityManager->getRepository(Product::class);
    }

    public function findOneByExternalIdAndAgencyExternalId($id, $externalAgencyId): ?Product
    {
        /** @var Product|null $product */
        $product = $this->repo->findOneBy([
            'externalId' => $id,
            'agency' => $externalAgencyId,
        ]);
        return $product;
    }
}