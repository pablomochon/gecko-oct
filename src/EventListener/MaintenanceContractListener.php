<?php

namespace App\EventListener;

use App\Entity\MaintenanceContract;
use App\Entity\MaintenanceContractLOG;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class MaintenanceContractListener
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function postPersist(MaintenanceContract $el, PostPersistEventArgs $args) { 
        $this->logActivity($el, $args, 'add');
    }

    public function postUpdate(MaintenanceContract $el, PostUpdateEventArgs $args) { 
        $this->logActivity($el, $args, 'edit');
    }

    private function logActivity(MaintenanceContract $el, $args, $action) {
        $log = new MaintenanceContractLOG();
        $log->setIdMaintenanceContract($el->getId());
        $log->setName($el->getName());
        $log->setStartDate($el->getStartDate());
        $log->setEndDate($el->getEndDate());
        $log->setManufacturer($el->getManufacturer());
        $log->setProvider($el->getProvider());
        $log->setStatus($el->isStatus());
        $log->setCost($el->getCost());
        $log->setNotes($el->getNotes());
        $log->setActive($el->isActive());
        $log->setAction($action);
        $log->setDateLOGValue(new \DateTime());

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