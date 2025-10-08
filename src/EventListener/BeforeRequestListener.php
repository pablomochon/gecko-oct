<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Bundle\SecurityBundle\Security;

class BeforeRequestListener
{
    private $em;
    private $security;

    public function __construct(EntityManager $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_NO_FILTER')) {
            return;
        }
        
        $clientIds = [0];

        $clientsFiltered = $user ? $user->getFilterClient() : [];
        foreach($clientsFiltered as $client) {
            $clientIds[] = $client->getId();
        }

        $filter = $this->em->getFilters()->enable('client_filter');
        $filter->setParameterList('id', $clientIds);

        $serviceIds = [];
        $servicesFiltered = $user ? $user->getFilterService() : [];
        foreach($servicesFiltered as $service) {
            $serviceIds[] = $service->getId();
        }

        if(count($serviceIds) > 0) {
            $filter = $this->em->getFilters()->enable('service_filter');
            $filter->setParameterList('id', $serviceIds);
        }
    }
}
