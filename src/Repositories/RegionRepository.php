<?php

namespace Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Entities\Region;

class RegionRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->repo = $entityManager->getRepository(Region::class);
    }

    public function findOneByExternalId(mixed $id): ?Region
    {
        /** @var Region|null $region */
        $region = $this->repo->findOneBy(['externalId' => $id]);
        return $region;
    }

    /**
     * @return Region[]
     */
    public function getAll(): array
    {
        return $this->repo->findAll();
    }
}