<?php

namespace App\Repository;

use App\Entity\NetworkVirtualSystem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetworkVirtualSystem>
 */
class NetworkVirtualSystemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetworkVirtualSystem::class);
    }

    public function countByClientActive($idClients, $name = null): int
    {
        $qb = $this->createQueryBuilder('ns')
            ->select(array('COUNT(ns) AS elementCount'))
            ->join('ns.networkDevice', 'nd')
            ->join('nd.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c')
            ->where('c.id IN (:idClients)')
            ->andWhere('ns.active = TRUE')
            ->setParameter('idClients', $idClients);

        if($name) {
            $qb
            ->andWhere('ns.name = :name')
            ->setParameter('name', $name);
        }
        
        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
            
        $qb = $this->createQueryBuilder('ns')
            ->select(array('COUNT(ns) AS elementCount'))
            ->join('ns.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c')
            ->where('c.id IN (:idClients)')
            ->andWhere('ns.active = TRUE')
            ->setParameter('idClients', $idClients);

        if($name) {
            $qb
            ->andWhere('ns.name = :name')
            ->setParameter('name', $name);
        }
        
        $result += $qb
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result;
    }
}
