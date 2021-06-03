<?php

namespace Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Entities\Agency;

class AgencyRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->repo = $entityManager->getRepository(Agency::class);
    }

    public function findOneByExternalId($id): ?Agency
    {
        /** @var Agency|null $agency */
        $agency = $this->repo->findOneBy(['externalId' => $id]);
        return $agency;
    }

    /**
     * @param $id
     * @return Agency
     * @throws \Exception
     */
    public function getByExternalId($id): Agency
    {
        $agency = $this->findOneByExternalId($id);
        if (!$agency) {
            throw new \Exception("Agency with externalId {$id} not found");
        }

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