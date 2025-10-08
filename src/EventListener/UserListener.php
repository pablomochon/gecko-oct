<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserLOG;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class UserListener
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function postPersist(User $user, PostPersistEventArgs $args) { 
        $this->logActivity($user, $args, 'add');
    }

    public function postUpdate(User $user, PostUpdateEventArgs $args) { 
        $this->logActivity($user, $args, 'edit');
    }

    private function logActivity(User $user, $args, $action) {
        $log = new UserLOG();
        $log->setIdUser($user->getId());
        $log->setName($user->getName());
        $log->setActive($user->isActive());
        $log->setEmail($user->getEmail());
        $log->setUsername($user->getUsername());
        $log->setRoles($user->getRoles());
        //$log->setRolesFixed($user->getRolesFixed());
        $log->setAction($action);
        $log->setUserLOG($this->security->getUser() ? $this->security->getUser() : $user);
        
        $entityManager = $args->getObjectManager();
        $entityManager->persist($log);
        $entityManager->flush();
    }
}
