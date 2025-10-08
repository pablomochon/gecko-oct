<?php

namespace App\EventListener;

use App\Entity\NetworkDevice;
use App\Entity\NetworkDeviceLOG;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class NetworkDeviceListener
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function postPersist(NetworkDevice $el, PostPersistEventArgs $args)
    {
        $this->logActivity($el, $args, 'add');
    }

    public function postUpdate(NetworkDevice $el, PostUpdateEventArgs $args)
    {
        $this->logActivity($el, $args, 'edit');
    }

    private function logActivity(NetworkDevice $el, $args, string $action): void
    {
        $log = new NetworkDeviceLOG();
        $log->setIdNetworkDevice($el->getId());
        $log->setName($el->getName());
        $log->setSerialNumber($el->getSerialNumber());
        $log->setDateLOGValue(new \DateTimeImmutable());
        $log->setActive($el->isActive());
        $log->setEnvironment($el->getEnvironment());
        $log->setAction($action);

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
