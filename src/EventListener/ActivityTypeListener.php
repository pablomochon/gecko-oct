<?php

namespace App\EventListener;

use App\Entity\ActivityType;
use App\Entity\ActivityTypeLOG;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class ActivityTypeListener
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function postPersist(ActivityType $activityType, PostPersistEventArgs $args) { 
        $this->logActivity($activityType, $args, 'add');
    }

    public function postUpdate(ActivityType $activityType, PostUpdateEventArgs $args) { 
        $this->logActivity($activityType, $args, 'edit');
    }

    private function logActivity(ActivityType $activityType, $args, $action) {
        $log = new ActivityTypeLOG();
        $log->setIdActivityType($activityType->getId());
        $log->setCode($activityType->getCode());
        $log->setDescription($activityType->getDescription());
        $log->setPrice($activityType->getPrice());
        $log->setSAPname($activityType->getSAPname());
        $log->setType($activityType->getType());
        $log->setActive($activityType->isActive());
        $log->setAction($action);
        
        // Usuario que realiza la acción
        $user = $this->security->getUser();
        $log->setUserLOG($user);
        
        // Registrar las relaciones
        $log->setNetworkDevices($activityType->getNetworkDevices());

        $log->setNetworkVirtualSystems($activityType->getNetworkVirtualSystems());
        // $log->setNetworkInterfaces($activityType->getNetworkInterfaces());
        
/*         $networkVirtualSystems = [];
        foreach ($activityType->getNetworkVirtualSystems() as $system) {
            $networkVirtualSystems[] = $system->getName();
        }
        $log->setNetworkVirtualSystems(implode(', ', $networkVirtualSystems));
        
        $networkInterfaces = [];
        foreach ($activityType->getNetworkInterfaces() as $interface) {
            $networkInterfaces[] = $interface->getName();
        }
        $log->setNetworkInterfaces(implode(', ', $networkInterfaces)); */
        
        $entityManager = $args->getObjectManager();
        $entityManager->persist($log);
        $entityManager->flush();
    }
}