<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NetworkDeviceRepository;
use App\Repository\NetworkVirtualSystemRepository;
use Doctrine\Persistence\ManagerRegistry;

class DashboardController extends AbstractController
{
    public function __construct(
        private NetworkDeviceRepository $networkDeviceRepository,
        private NetworkVirtualSystemRepository $networkVirtualSystemsRepository)
    {
        $this->networkDeviceRepository = $networkDeviceRepository;
        $this->networkVirtualSystemsRepository = $networkVirtualSystemsRepository;
    }

    #[Route('/', name: 'app_dashboard')]
    public function index(ManagerRegistry $doctrine): Response
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        if(isset($em->getFilters()->getEnabledFilters()['client_filter'])) {
            $filteredClients = explode(',', str_replace("'", "", $em->getFilters()->getEnabledFilters()['client_filter']->getParameterList('id')));
            $networkDevices = $this->networkDeviceRepository->countByClientActive($filteredClients);
            $networkVirtualSystems = $this->networkVirtualSystemsRepository->countByClientActive($filteredClients);
        } else {
            $networkDevices = $this->networkDeviceRepository->count(array('active' => true));
            $networkVirtualSystems = $this->networkVirtualSystemsRepository->count(array('active' => true));
        }

        return $this->render('dashboard/index.html.twig', [
            'pageTitle' => 'Dashboard',
            'numNetworkDevices' => $networkDevices,
            'numNetworkVirtualSystems' => $networkVirtualSystems,
        ]);
    }
}
