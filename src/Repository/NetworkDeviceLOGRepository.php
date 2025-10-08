<?php

namespace App\Repository;

use App\Entity\NetworkDeviceLOG;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetworkDeviceLOG>
 */
class NetworkDeviceLOGRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetworkDeviceLOG::class);
    }
    
}
