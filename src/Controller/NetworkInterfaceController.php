<?php

namespace App\Controller;

ini_set('memory_limit', -1);

use App\Entity\NetworkInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\NetworkInterfaceLOGRepository;
use App\Repository\NetworkInterfaceRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/networkInterfaces')]
class NetworkInterfaceController extends AbstractController
{
    var $basePath = '';

    public function __construct(
        KernelInterface $appKernel,
        private NetworkInterfaceRepository $networkInterfaceRepository,
        private NetworkInterfaceLOGRepository $networkInterfaceLogRepository
    ) {
        $this->basePath = $appKernel->getProjectDir();
    }

    #[Route('/', name: 'app_networkInterfaces', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('networkInterfaces/index.html.twig', [
            'pageTitle'  => 'Network Interfaces',
            'templatemtime' => str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/networkInterfaces/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_networkInterfacesPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine): Response
    {
        $params = json_decode($request->query->get('params'), true);
        $sortingBy = isset($params['sort']) ? $params['sort'] : [];
        
        // ABUELO CONSULTA networkdevice
        // Definimos las columnas y la relación con nd y ns
        $columns = [
            'id' => 'ni.id',
            'name' => 'ni.name',
            'active' => 'ni.active',
            'macAddress' => 'ni.macAddress',
            'defaultGateway' => 'ni.defaultGateway',
            'dhcpEnabled' => 'ni.dhcpEnabled',
            'dhcpServer' => 'ni.dhcpServer',
            'dnsHostname' => 'ni.dnsHostname',
            'dnsDomain' => 'ni.dnsDomain',
            'dnsServer' => 'ni.dnsServer',
            'adapterType' => 'ni.adapterType',
            'description' => 'ni.description',
            'comments' => 'ni.comments',
            'networkVirtualSystemName' => 'ns.name',
            'networkDeviceName' => 'nd.name',
            'environmentName' => 'e.name',
            'serviceName' => 's.name',
            'clientName' => 'c.name',
        ];

        $joinTables = array(
            'ns' => array('ns'),
            'nd' => array('ns', 'nd'),
            'e' => array('ns', 'nd', 'e'),
            's' => array('ns', 'nd', 'e', 's'),
            'c' => array('ns', 'nd', 'e','s','c'),
        );

        // INICIALIZAR TABLE
        $table = array();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(NetworkInterface::class, 'ni');
        $query->select('ni');

        // Filtro por estado activo/inactivo
        if ($request->get('active') === "false") {
            $query->andWhere('ni.active = FALSE');
        } else {
            $query->andWhere('ni.active = TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }

        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        //Para filtrar por cliente
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'clientName' || key($column) == 'id') {
                $columnVisibility[$columnKey][key($column)] = true;
            }
        }
        foreach($columnVisibility as $column) {
            if($column[key($column)] && isset($columns[key($column)])) {
                $query->addSelect($columns[key($column)] . ' AS ' . key($column));

                // Se encuentra en las tablas que "necesitan" joins, pero actualmente no está el join en la query
                if(in_array(explode('.', $columns[key($column)])[0], array_keys($joinTables)) && !in_array(explode('.', $columns[key($column)])[0], $joinedTables)) {
                    foreach($joinTables[explode('.', $columns[key($column)])[0]] as $tableToJoin) {
                        if(!in_array($tableToJoin, $joinedTables)) {
                            if($request->get('active') === "false") {
                                switch($tableToJoin) {
                                    case 'ns':
                                        $query->join('ni.networkVirtualSystem', 'ns');
                                        break;
                                    case 'nd':
                                        $query->join('ns.networkDevice', 'nd');
                                        break;
                                    case 'e':
                                        $query->join('nd.environment', 'e');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'ns':
                                        $query->join('ni.networkVirtualSystem', 'ns', \Doctrine\ORM\Query\Expr\Join::WITH, 'ns.active=TRUE');
                                        break;
                                    case 'nd':
                                        $query->join('ns.networkDevice', 'nd', \Doctrine\ORM\Query\Expr\Join::WITH, 'nd.active=TRUE');
                                        break;
                                    case 'e':
                                        $query->join('nd.environment', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.active=TRUE');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.active=TRUE');
                                        break;
                                }
                            }
                            $joinedTables[] = $tableToJoin;
                        }
                    }
                }
            }
        }

        foreach($query->getQuery()->getScalarResult() as $result) {
            if(isset($table[$result['id']])) {
                foreach($columnVisibility as $column) {
                    if($column[key($column)] && key($column) != 'id' && in_array(key($column), array('roleSecondary'))) {
                        switch(key($column)) {
                            default:
                                if(strpos($table[$result['id']][key($column)], $result[key($column)]) === false) {
                                    $table[$result['id']][key($column)] .= ', ' . $result[key($column)];
                                }
                        }
                    }
                }
            } else {
                $table[$result['id']] = array(
                    'id' => $result['id'],
                );
                
                foreach($columnVisibility as $column) {
                    if($column[key($column)] && key($column) != 'id') {
                        switch(key($column)) {
                            default:
                                $table[$result['id']][key($column)] = $result[key($column)];
                        }
                    }
                }
            }
        }

        // PADRE CONSULTA networkvirtualsystem
        // Definimos solo las columnas y la relación con ns
        $columns = [
            'id' => 'ni.id',
            'name' => 'ni.name',
            'active' => 'ni.active',
            'macAddress' => 'ni.macAddress',
            'defaultGateway' => 'ni.defaultGateway',
            'dhcpEnabled' => 'ni.dhcpEnabled',
            'dhcpServer' => 'ni.dhcpServer',
            'dnsHostname' => 'ni.dnsHostname',
            'dnsDomain' => 'ni.dnsDomain',
            'dnsServer' => 'ni.dnsServer',
            'adapterType' => 'ni.adapterType',
            'description' => 'ni.description',
            'comments' => 'ni.comments',
            'networkVirtualSystemName' => 'ns.name',
            'environmentName' => 'e.name',
            'serviceName' => 's.name',
            'clientName' => 'c.name',
        ];

        $joinTables = array(
            'ns' => array('ns'),
            'e' => array('ns', 'e'),
            's' => array('ns', 'e', 's'),
            'c' => array('ns', 'e','s','c'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(NetworkInterface::class, 'ni');
        $query->select('ni');

        // Filtro por estado activo/inactivo
        if ($request->get('active') === "false") {
            $query->andWhere('ni.active = FALSE');
        } else {
            $query->andWhere('ni.active = TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }

        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        //Para filtrar por cliente
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'clientName' || key($column) == 'id') {
                $columnVisibility[$columnKey][key($column)] = true;
            }
        }
        foreach($columnVisibility as $column) {
            if($column[key($column)] && isset($columns[key($column)])) {
                $query->addSelect($columns[key($column)] . ' AS ' . key($column));

                // Se encuentra en las tablas que "necesitan" joins, pero actualmente no está el join en la query
                if(in_array(explode('.', $columns[key($column)])[0], array_keys($joinTables)) && !in_array(explode('.', $columns[key($column)])[0], $joinedTables)) {
                    foreach($joinTables[explode('.', $columns[key($column)])[0]] as $tableToJoin) {
                        if(!in_array($tableToJoin, $joinedTables)) {
                            if($request->get('active') === "false") {
                                switch($tableToJoin) {
                                    case 'ns':
                                        $query->join('ni.networkVirtualSystem', 'ns');
                                        break;
                                    case 'e':
                                        $query->join('ns.environment', 'e');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'ns':
                                        $query->join('ni.networkVirtualSystem', 'ns', \Doctrine\ORM\Query\Expr\Join::WITH, 'ns.active=TRUE');
                                        break;
                                    case 'e':
                                        $query->join('ns.environment', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.active=TRUE');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.active=TRUE');
                                        break;
                                }
                            }
                            $joinedTables[] = $tableToJoin;
                        }
                    }
                }
            }
        }

        foreach($query->getQuery()->getScalarResult() as $result) {
            foreach($columnVisibility as $column) {
                if($column[key($column)] && in_array(key($column), array_keys($columns))) {
                    switch(key($column)) {
                        default:
                            $table[$result['id']][key($column)] = $result[key($column)];
                    }
                }
            }
        }

        // HIJO CONSULTA netinterface
        // Definimos solo las columnas básicas sin relaciones
        $columns = [
            'id' => 'ni.id',
            'name' => 'ni.name',
            'active' => 'ni.active',
            'macAddress' => 'ni.macAddress',
            'defaultGateway' => 'ni.defaultGateway',
            'dhcpEnabled' => 'ni.dhcpEnabled',
            'dhcpServer' => 'ni.dhcpServer',
            'dnsHostname' => 'ni.dnsHostname',
            'dnsDomain' => 'ni.dnsDomain',
            'dnsServer' => 'ni.dnsServer',
            'adapterType' => 'ni.adapterType',
            'description' => 'ni.description',
            'comments' => 'ni.comments',
            'environmentName' => 'e.name',
            'serviceName' => 's.name',
            'clientName' => 'c.name',
        ];

        $joinTables = array(
            'ni' => array('ni'),
            'e' => array('ni', 'e'),
            's' => array('ni', 'e','s'),
            'c' => array('ni', 'e','s','c'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(NetworkInterface::class, 'ni');
        $query->select('ni');

        // Filtro por estado activo/inactivo
        if ($request->get('active') === "false") {
            $query->andWhere('ni.active = FALSE');
        } else {
            $query->andWhere('ni.active = TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }

        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        //Para filtrar por cliente
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'clientName' || key($column) == 'id') {
                $columnVisibility[$columnKey][key($column)] = true;
            }
        }
        foreach($columnVisibility as $column) {
            if($column[key($column)] && isset($columns[key($column)])) {
                $query->addSelect($columns[key($column)] . ' AS ' . key($column));

                // Se encuentra en las tablas que "necesitan" joins, pero actualmente no está el join en la query
                if(in_array(explode('.', $columns[key($column)])[0], array_keys($joinTables)) && !in_array(explode('.', $columns[key($column)])[0], $joinedTables)) {
                    foreach($joinTables[explode('.', $columns[key($column)])[0]] as $tableToJoin) {
                        if(!in_array($tableToJoin, $joinedTables)) {
                            if($request->get('active') === "false") {
                                switch($tableToJoin) {
                                    case 'e':
                                        $query->join('ni.environment', 'e');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'e':
                                        $query->join('ni.environment', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.active=TRUE');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
                                        break;
                                }
                            }
                            $joinedTables[] = $tableToJoin;
                        }
                    }
                }
            }
        }
        
        foreach($query->getQuery()->getScalarResult() as $result) {
            foreach($columnVisibility as $column) {
                if($column[key($column)] && in_array(key($column), array_keys($columns))) {
                    switch(key($column)) {
                        default:
                            $table[$result['id']][key($column)] = $result[key($column)];
                    }
                }
            }
        }

        return new Response(
            json_encode(array_values($table)),
            Response::HTTP_OK,
        );
    }

    #[Route('/select2/data', name: 'app_select2networkInterfacesData')]
    public function select2networkInterfacesData(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        $limit = 5;
        $term = $request->query->get('search');

        $em = $doctrine->getManager();

        $query = $em
            ->getRepository(NetworkInterface::class)
            ->createQueryBuilder('ni')
            
            ->where('ni.name LIKE :val')
            ->andWhere('ni.active = TRUE')
            ->setParameter('val', "%$term%");
        
        if($limit === true) {
            $result = $query->getQuery()->getResult();
            $response['count'] = count($result);
            $response['last_page'] = 1;
        } else {
            $pagination = $paginator->paginate(
                $query, /* query NOT result */
                $request->query->getInt('page', $page), /*page number*/
                $limit, /*limit per page*/
                array('distinct' => true, 'wrap-queries' => true)
            );

            $response['count'] = $pagination->getTotalItemCount();
            $response['last_page'] = ceil($pagination->getTotalItemCount() / $limit);
            $result = $pagination->getItems();
        }
        
        foreach($result as $item) {
            $element = array();
            $element['id'] = $item->getId();
            $response['results'][] = $element;
        }

        $query = $em
            ->getRepository(NetworkInterface::class)
            ->createQueryBuilder('ni')
            ->where('ni.name LIKE :val OR ns.name LIKE :val OR nd.name LIKE :val')
            ->andWhere('ni.active = TRUE')
            ->setParameter('val', "%$term%");
        
        if(isset($em->getFilters()->getEnabledFilters()['client_filter'])) {
            $query
            ->join('ni.networkVirtualSystem', 'ns')
            ->join('ns.networkDevice', 'nd')
            ->join('nd.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c');
        } else {
            $query
            ->leftJoin('ni.networkVirtualSystem', 'ns')
            ->leftJoin('ns.networkDevice', 'nd');
        }
        
        if($limit === true) {
            $result = $query->getQuery()->getResult();
            $response['count'] = count($result);
            $response['last_page'] = 1;
        } else {
            $pagination = $paginator->paginate(
                $query, /* query NOT result */
                $request->query->getInt('page', $page), /*page number*/
                $limit, /*limit per page*/
                array('distinct' => true, 'wrap-queries' => true)
            );

            $response['count'] = $pagination->getTotalItemCount();
            $response['last_page'] = ceil($pagination->getTotalItemCount() / $limit);
            $result = $pagination->getItems();
        }
        
        foreach($result as $item) {
            $element = array();
            $element['id'] = $item->getId();
            if($item->getNetworkVirtualSystem()) {
                $element['text'] = $item->getNetworkVirtualSystem()->getNetworkDevice()->getName() . ' (' . $item->getNetworkVirtualSystem()->getName() . '): ' . $item->getName();
            }
            $response['results'][] = $element;
        }

        if((int) $response['last_page'] == (int) $page || (int) $response['last_page'] == 0){
            $response['pagination']['more'] = false;
        } else {
            $response['pagination']['more'] = true;
        }
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/select2/data/{id}', name: 'app_select2networkInterfaceDataId')]
    public function select2networkInterfaceDataId(NetworkInterface $networkInterface): Response
    {
        $response['id'] = $networkInterface->getId();
        if ($networkInterface->getNetworkVirtualSystem()) {
            $response['text'] = $networkInterface->getNetworkVirtualSystem()->getNetworkDevice()->getName() . ' (' . $networkInterface->getNetworkVirtualSystem()->getName() . '): ' . $networkInterface->getName();
        }
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_networkInterfacesDetail')]
    public function networkInterfacesDetail(NetworkInterface $networkInterface, TranslatorInterface $translator): Response
    {
        // $this->denyAccessUnlessGranted('view', $networkInterface);
        return $this->render('_detail.html.twig', [
            'pageTitle' => $translator->trans('Network Device') . ' ' . $networkInterface->getName(),
            'pageView' => $this->generateUrl('app_networkInterfacesView', ['id' => $networkInterface->getId()]),
            'auditView' => $this->generateUrl('app_networkInterfacesAudit', ['id' => $networkInterface->getId()]),
            'object'  => $networkInterface,
        ]);
    }

    #[Route('/{id}/view', name: 'app_networkInterfacesView')]
    public function networkInterfacesView(NetworkInterface $networkInterface, TranslatorInterface $translator): Response
    {
        // $this->denyAccessUnlessGranted('view', $networkInterface);
        return $this->render('networkInterfaces/_networkInterfacesView.html.twig', [
            'pageTitle' => $translator->trans('Network Interface') . ' ' . $networkInterface->getName(),
            'object'  => $networkInterface,
        ]);
    }

    #[Route('/{id}/audit', name: 'app_networkInterfacesAudit')]
    public function networkInterfacesAudit(NetworkInterface $networkInterface, Request $request): Response
    {
        // $this->denyAccessUnlessGranted('view', $networkInterface);
        $changes = array();
        $logs = $this->networkInterfaceLogRepository->findBy(array('idNetworkInterface' => $networkInterface->getId()), array('DateLOG' => 'ASC'));

        foreach ($logs as $key => $log) {
            if ($key === 0) {
                $changes[] = array(
                    'field' => 'Name',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getName(),
                    'after' => $log->getName(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'Description',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getDescription(),
                    'after' => $log->getDescription(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'Comments',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getComments(),
                    'after' => $log->getComments(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                ); 
                $changes[] = array(
                    'field' => 'Network Virtual System',
                    'before' => ($key === 0) ? '' : ($logs[$key - 1]->getNetworkVirtualSystem() ? $logs[$key - 1]->getNetworkVirtualSystem()->getName() : ''),
                    'after' => $log->getNetworkVirtualSystem() ? $log->getNetworkVirtualSystem()->getName() : '',
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'Active',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->isActive(),
                    'after' => $log->isActive(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'MAC Address',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getMacAddress(),
                    'after' => $log->getMacAddress(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'Default Gateway',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getDefaultGateway(),
                    'after' => $log->getDefaultGateway(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'DHCP Enabled',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->isDhcpEnabled(),
                    'after' => $log->isDhcpEnabled(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'DHCP Server',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getDhcpServer(),
                    'after' => $log->getDhcpServer(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'DNS hostname',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getDnsHostname(),
                    'after' => $log->getDnsHostname(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'DNS domain',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getDnsDomain(),
                    'after' => $log->getDnsDomain(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'DNS server',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getDnsServer(),
                    'after' => $log->getDnsServer(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'Adapter Type',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getAdapterType(),
                    'after' => $log->getAdapterType(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                ); 
                $changes[] = array(
                    'field' => 'Environment',
                    'before' => ($key === 0) ? '' : ($logs[$key - 1]->getEnvironment() ? $logs[$key - 1]->getEnvironment()->getName() : ''),
                    'after' => $log->getEnvironment() ? $log->getEnvironment()->getName() : '',
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
            } else {
                if ($log->getName() !== $logs[$key - 1]->getName()) {
                    $changes[] = array(
                        'field' => 'Name',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getName(),
                        'after' => $log->getName(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getDescription() !== $logs[$key - 1]->getDescription()) {
                    $changes[] = array(
                        'field' => 'Description',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getDescription(),
                        'after' => $log->getDescription(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getComments() !== $logs[$key - 1]->getComments()) {
                    $changes[] = array(
                        'field' => 'Comments',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getComments(),
                        'after' => $log->getComments(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }                 
                if ($log->getNetworkVirtualSystem() !== $logs[$key - 1]->getNetworkVirtualSystem()) {
                    $changes[] = array(
                        'field' => 'Network Virtual System',
                        'before' => ($key === 0) ? '' : ($logs[$key - 1]->getNetworkVirtualSystem() ? $logs[$key - 1]->getNetworkVirtualSystem()->getName() : ''),
                        'after' => $log->getNetworkVirtualSystem() ? $log->getNetworkVirtualSystem()->getName() : '',
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->isActive() !== $logs[$key - 1]->isActive()) {
                    $changes[] = array(
                        'field' => 'Active',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->isActive(),
                        'after' => $log->isActive(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getMacAddress() !== $logs[$key - 1]->getMacAddress()) {
                    $changes[] = array(
                        'field' => 'MAC Address',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getMacAddress(),
                        'after' => $log->getMacAddress(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getDefaultGateway() !== $logs[$key - 1]->getDefaultGateway()) {
                    $changes[] = array(
                        'field' => 'Default Gateway',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getDefaultGateway(),
                        'after' => $log->getDefaultGateway(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->isDhcpEnabled() !== $logs[$key - 1]->isDhcpEnabled()) {
                    $changes[] = array(
                        'field' => 'DHCP Enabled',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->isDhcpEnabled(),
                        'after' => $log->isDhcpEnabled(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getDhcpServer() !== $logs[$key - 1]->getDhcpServer()) {
                    $changes[] = array(
                        'field' => 'DHCP Server',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getDhcpServer(),
                        'after' => $log->getDhcpServer(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getDnsHostname() !== $logs[$key - 1]->getDnsHostname()) {
                    $changes[] = array(
                        'field' => 'DNS hostname',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getDnsHostname(),
                        'after' => $log->getDnsHostname(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getDnsDomain() !== $logs[$key - 1]->getDnsDomain()) {
                    $changes[] = array(
                        'field' => 'DNS domain',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getDnsDomain(),
                        'after' => $log->getDnsDomain(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getDnsServer() !== $logs[$key - 1]->getDnsServer()) {
                    $changes[] = array(
                        'field' => 'DNS server',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getDnsServer(),
                        'after' => $log->getDnsServer(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getAdapterType() !== $logs[$key - 1]->getAdapterType()) {
                    $changes[] = array(
                        'field' => 'Name',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getAdapterType(),
                        'after' => $log->getAdapterType(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                } 
                if ($log->getEnvironment() !== $logs[$key - 1]->getEnvironment()) {
                    $changes[] = array(
                        'field' => 'Environment',
                        'before' => ($key === 0) ? '' : ($logs[$key - 1]->getEnvironment() ? $logs[$key - 1]->getEnvironment()->getName() : ''),
                        'after' => $log->getEnvironment() ? $log->getEnvironment()->getName() : '',
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
            }
        }

        $changesOrdered = array();
        foreach (array_reverse($changes) as $change) {
            $changesOrdered[$change['DateLOG']->format('Y-m-d H:i:s')][$change['UserLOGuser']][] = $change;
        }

        if ($request->get('format') == 'application/json') {
            return new JsonResponse($changesOrdered, Response::HTTP_OK);
        } else {
            return $this->render('_auditViewer.html.twig', [
                'changes'  => $changesOrdered,
            ]);
        }
    }
}
