<?php

namespace App\Repository;

use App\Entity\TableFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TableFilter>
 */
class TableFilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TableFilter::class);
    }

    /**
     * Find filters for a specific table and user
     */
    public function findByTableIdAndUser(string $tableId, int $userId): array
    {
        return $this->createQueryBuilder('tf')
            ->andWhere('tf.tableId = :tableId')
            ->andWhere('tf.user = :userId')
            ->setParameter('tableId', $tableId)
            ->setParameter('userId', $userId)
            ->orderBy('tf.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find public filters for a specific table
     */
    public function findPublicByTableId(string $tableId): array
    {
        return $this->createQueryBuilder('tf')
            ->andWhere('tf.tableId = :tableId')
            ->andWhere('tf.isPublic = :isPublic')
            ->setParameter('tableId', $tableId)
            ->setParameter('isPublic', true)
            ->orderBy('tf.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

}