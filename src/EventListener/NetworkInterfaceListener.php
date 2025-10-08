<?php

namespace App\EventListener;

use App\Entity\NetworkInterface;
use App\Entity\NetworkInterfaceLOG;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class NetworkInterfaceListener
{
    private $security;

    public function __construct(Security $security, private UserRepository $userRepository)
    {
        $this->security = $security;
    }

    public function postPersist(NetworkInterface $el, PostPersistEventArgs $args) { 
        $this->logActivity($el, $args, 'add');
    }

    public function postUpdate(NetworkInterface $el, PostUpdateEventArgs $args) { 
        $this->logActivity($el, $args, 'edit');
    }

    private function logActivity(NetworkInterface $el, $args, $action) {
        $log = new NetworkInterfaceLOG();
        $log->setIdNetworkInterface($el->getId());
        $log->setName($el->getName());
        $log->setNetworkVirtualSystem($el->getNetworkVirtualSystem());
        $log->setActive($el->isActive());
        $log->setMacAddress($el->getMacAddress());
        $log->setDefaultGateway($el->getDefaultGateway());
        $log->setDhcpEnabled($el->isDhcpEnabled());
        $log->setDhcpServer($el->getDhcpServer());
        $log->setDnsHostname($el->getDnsHostname());
        $log->setDnsDomain($el->getDnsDomain());
        $log->setDnsServer($el->getDnsServer());
        $log->setAdapterType($el->getAdapterType());
        $log->setEnvironment($el->getEnvironment());
        $log->setDescription($el->getDescription());
        $log->setComments($el->getComments());
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
