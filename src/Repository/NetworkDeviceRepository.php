<?php

namespace App\Repository;

use App\Entity\NetworkDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetworkDevice>
 */
class NetworkDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetworkDevice::class);
    }

    public function countByClientActive($idClients, $name = null): int
    {
        $qb = $this->createQueryBuilder('nd')
            ->select(array('COUNT(nd) AS elementCount'))
            ->join('nd.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c')
            ->where('c.id IN (:idClients)')
            ->andWhere('nd.active = TRUE')
            ->setParameter('idClients', $idClients);

        if($name) {
            $qb
            ->andWhere('nd.name = :name')
            ->setParameter('name', $name);
        }
        
        return $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

}
