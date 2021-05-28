<?php

namespace Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Entities\Agency;
use Entities\Region;

class AgencyRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->repo = $entityManager->getRepository(Agency::class);
    }

    public function findOneByExternalId(mixed $id): ?Agency
    {
        /** @var Agency|null $agency */
        $agency = $this->repo->findOneBy(['externalId' => $id]);
        return $agency;
    }

    /**
     * @return Agency[]
     */
    public function getAll(): array
    {
        return $this->repo->findAll();
    }
}