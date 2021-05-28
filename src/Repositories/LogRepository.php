<?php

namespace Repositories;

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
            ->where('dt > :dt')
            ->setParameter('dt', $dt)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}