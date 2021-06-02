<?php

namespace Repositories;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Entities\Log;

class LogRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->repo = $entityManager->getRepository(Log::class);
    }

    /**
     * @param \DateTime $dt
     * @return Log|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastOneSinceDt(\DateTime $dt): ?Log
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Log::class, 'l')
            ->where('l.dt > :dt')
            ->setParameter('dt', $dt, Types::DATETIME_MUTABLE)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()
            ->getSingleResult();
    }
}