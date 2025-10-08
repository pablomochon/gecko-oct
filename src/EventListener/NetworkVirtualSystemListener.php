<?php

namespace App\EventListener;

use App\Entity\NetworkVirtualSystem;
use App\Entity\NetworkVirtualSystemLOG;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class NetworkVirtualSystemListener
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function postPersist(NetworkVirtualSystem $el, PostPersistEventArgs $args)
    {
        $this->logActivity($el, $args, 'add');
    }

    public function postUpdate(NetworkVirtualSystem $el, PostUpdateEventArgs $args)
    {
        $this->logActivity($el, $args, 'edit');
    }

    public function logActivity(NetworkVirtualSystem $el, $args, string $action): void
    {
        $log = new NetworkVirtualSystemLOG();
        $log->setIdNetworkVirtualSystem($el->getId());
        $log->setName($el->getName());
        $log->setDateLOGValue(new \DateTimeImmutable());
        $log->setActive($el->isActive());
        $log->setEnvironment($el->getEnvironment());
        $log->setAction($action);
        $log->setRoleSecondary($el->getRoleSecondary());
        $log->setRole($el->getRole());

        if ('cli' === PHP_SAPI) {
            $user = $this->userRepository->findOneBy(array('username' => 'housekeeper'));
            $log->setUserLOG($user);
        } else {
            $log->setUserLOG($this->security->getUser());
        }

        $entityManager = $args->getObjectManager();
        $entityManager->persist($log);
        $entityManager->flush();
    }
}
