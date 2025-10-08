<?php

namespace App\EventListener;

use App\Entity\Environment;
use App\Entity\EnvironmentLOG;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class EnvironmentListener
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function postPersist(Environment $el, PostPersistEventArgs $args) { 
        $this->logActivity($el, $args, 'add');
    }

    public function postUpdate(Environment $el, PostUpdateEventArgs $args) { 
        $this->logActivity($el, $args, 'edit');
    }

    private function logActivity(Environment $el, $args, $action) {
        $log = new EnvironmentLOG();
        $log->setIdEnvironment($el->getId());
        $log->setName($el->getName());
        $log->setService($el->getService());
        $log->setType($el->getType());
        $log->setActive($el->isActive());
        $log->setAction($action);
        $log->setUserLOG($this->security->getUser());
        
        $entityManager = $args->getObjectManager();
        $entityManager->persist($log);
        $entityManager->flush();
    }
}
