<?php

namespace App\EventListener;

use App\Entity\Client;
use App\Entity\ClientLOG;
use App\Entity\NetworkDevice;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class ClientListener
{
    public function __construct(private Security $security) {}

    public function postPersist(Client $el, PostPersistEventArgs $args) { 
        $this->logActivity($el, $args, 'add');
    }

    public function postUpdate(Client $el, PostUpdateEventArgs $args) { 
        $this->logActivity($el, $args, 'edit');
    }

    private function logActivity(Client $el, $args, $action) {
        $log = new ClientLOG();
        $log->setIdClient($el->getId());
        $log->setName($el->getName());
        $log->setCode($el->getCode());
        $log->setActive($el->isActive());
        $log->setAction($action);
        $log->setUserLOG($this->security->getUser());
        
        $entityManager = $args->getObjectManager();
        $entityManager->persist($log);
        $entityManager->flush();
    }
}