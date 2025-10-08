<?php

namespace App\EventListener;

use App\Entity\Service;
use App\Entity\ServiceLOG;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class ServiceListener
{
    public function __construct(private Security $security) {}

    public function postPersist(Service $el, PostPersistEventArgs $args)
    {
        $this->logActivity($el, $args, 'add');
    }

    public function postUpdate(Service $el, PostUpdateEventArgs $args)
    {
        $this->logActivity($el, $args, 'edit');
    }

    private function logActivity(Service $el, $args, $action)
    {
        $log = new ServiceLOG();
        $log->setIdService($el->getId());
        $log->setName($el->getName());
        $log->setTcosrv($el->getTcosrv());
        $log->setPep($el->getPep());
        $log->setDescription($el->getDescription());
        $log->setActive($el->isActive());
        $log->setClient($el->getClient());
        $log->setAction($action);
        $log->setUserLOG($this->security->getUser());

        $entityManager = $args->getObjectManager();
        $entityManager->persist($log);
        $entityManager->flush();
    }
}
