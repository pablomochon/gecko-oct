<?php

namespace App\Repository;

use App\Entity\SamlProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SamlProfile>
 *
 * @method SamlProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method SamlProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method SamlProfile[]    findAll()
 * @method SamlProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SamlProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SamlProfile::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(SamlProfile $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SamlProfile $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
