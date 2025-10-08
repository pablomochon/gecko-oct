<?php

namespace App\Repository;

use App\Entity\NetworkVirtualSystemRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetworkVirtualSystemRole>
 *
 * @method NetworkVirtualSystemRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method NetworkVirtualSystemRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method NetworkVirtualSystemRole[]    findAll()
 * @method NetworkVirtualSystemRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NetworkVirtualSystemRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetworkVirtualSystemRole::class);
    }
}
