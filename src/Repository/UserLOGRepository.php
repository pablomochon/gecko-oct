<?php

namespace App\Repository;

use App\Entity\UserLOG;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLOG>
 *
 * @method UserLOG|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserLOG|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserLOG[]    findAll()
 * @method UserLOG[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserLOGRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLOG::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(UserLOG $entity, bool $flush = false): void
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
    public function remove(UserLOG $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

}
