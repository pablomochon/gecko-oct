<?php

namespace App\Repository;

use App\Entity\NetworkInterfaceLOG;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @extends ServiceEntityRepository<NetworkInterfaceLOG>
 *
 * @method NetworkInterfaceLOG|null find($id, $lockMode = null, $lockVersion = null)
 * @method NetworkInterfaceLOG|null findOneBy(array $criteria, array $orderBy = null)
 * @method NetworkInterfaceLOG[]    findAll()
 * @method NetworkInterfaceLOG[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NetworkInterfaceLOGRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetworkInterfaceLOG::class);
    }

    public function add(NetworkInterfaceLOG $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NetworkInterfaceLOG $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
