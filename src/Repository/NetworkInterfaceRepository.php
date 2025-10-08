<?php

namespace App\Repository;

use App\Entity\NetworkInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetworkInterface>
 */
class NetworkInterfaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetworkInterface::class);
    }

    public function add(NetworkInterface $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NetworkInterface $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return NetworkInterface[] Returns an array of NetworkInterface objects
     */
    public function findByAnyField($value): array
    {
        $results = [];
        $query = $this->createQueryBuilder('ni')
        ->where('ni.active = TRUE')
        ->andWhere('ni.macAddress LIKE :val')
        ->setParameter('val', "%$value%");
        
        if(isset($this->getEntityManager()->getFilters()->getEnabledFilters()['client_filter'])) {
            $query
            ->join('ni.networkVirtualSystem', 'ns')
            ->join('ns.networkDevice', 'nd')
            ->join('nd.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c');
        }
        $results += $query
        ->setCacheable(true)
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

        $query = $this->createQueryBuilder('ni')
        ->where('ni.active = TRUE')
        ->andWhere('ni.macAddress LIKE :val')
        ->setParameter('val', "%$value%");
        
        if(isset($this->getEntityManager()->getFilters()->getEnabledFilters()['client_filter'])) {
            $query
            ->join('ni.workplaceVirtualSystem', 'ws')
            ->join('ws.workplaceHardware', 'wh')
            ->join('wh.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c');
        }
        $results += $query
        ->setCacheable(true)
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

        return $results;
    }
}