<?php

namespace App\Controller;

use App\Entity\NetworkVirtualSystem;
use App\Repository\NetworkVirtualSystemLOGRepository;
use App\Repository\NetworkVirtualSystemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/network/virtualsystems')]
class NetworkVirtualSystemController extends AbstractController
{
    public $basePath = '';

    public function __construct(
        KernelInterface $appKernel,
        private NetworkVirtualSystemRepository $networkVirtualSystemRepository,
        private NetworkVirtualSystemLOGRepository $networkVirtualSystemLogRepository,
    ) {
        $this->basePath = $appKernel->getProjectDir();
    }

    #[Route('/', name: 'app_network_virtual_system', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('network/virtual_systems/index.html.twig', [
            'pageTitle'  => 'Network Virtual Systems',
            'templatemtime' => str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/network/virtual_systems/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_networkVirtualSystemsPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine): Response
    {
        $params = json_decode($request->query->get('params'), true);
        $sortingBy = isset($params['sort']) ? $params['sort'] : [];

        // CONSULTA CON RELACION CON NETWORKDEVICE
        $columns = [
            'id' => 'ns.id',
            'name' => 'ns.name',
            'active' => 'ns.active',
            'environmentName' => 'e.name',
            'serviceName' => 's.name',
            'clientName' => 'c.name',
            'networkDeviceName' => 'nd.name',
            'role' => 'nsr.name',
            'roleCode' => 'nsr.code',
            'roleSecondary' => 'nsrr.name',
        ];

        $joinTables = array(
            'nd' => array('nd'),
            'e' => array('nd','e'),
            's' => array('nd','e','s'),
            'c' => array('nd','e','s','c'),
            'nsr' => array('nsr'),
            'nsrr' => array('nsrr'),
        );

        // INICIALIZAR TABLE
        $table = array();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(NetworkVirtualSystem::class, 'ns');
        $query->select('ns'); // Seleccionamos el objeto completo

        // Filtro por estado activo/inactivo
        if ($request->get('active') === "false") {
            $query->andWhere('ns.active = FALSE');
        } else {
            $query->andWhere('ns.active = TRUE');
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
                                    case 'nd':
                                        $query->join('ns.networkDevice', 'nd');
                                        break;
                                    case 'e':
                                        // en la tcmdb es nd.environment
                                        $query->join('nd.environment', 'e');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c');
                                        break;
                                    case 'nsr':
                                        $query->leftJoin('ns.role', 'nsr');
                                        break;
                                    case 'nsrr':
                                        $query->leftJoin('ns.roleSecondary', 'nsrr');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'nd':
                                        $query->join('ns.networkDevice', 'nd', \Doctrine\ORM\Query\Expr\Join::WITH, 'nd.active=TRUE');
                                        break;
                                    case 'e':
                                        // en la tcmdb es nd.environment
                                        $query->join('nd.environment', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.active=TRUE');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.active=TRUE');
                                        break;
                                    case 'nsr':
                                        $query->leftJoin('ns.role', 'nsr');
                                        break;
                                    case 'nsrr':
                                        $query->leftJoin('ns.roleSecondary', 'nsrr');
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

        
        // CONSULTA SIN RELACIONES
        $columns = [
            'id' => 'ns.id',
            'name' => 'ns.name',
            'active' => 'ns.active',
            'environmentName' => 'e.name',
            'serviceName' => 's.name',
            'clientName' => 'c.name',
            'role' => 'nsr.name',
            'roleCode' => 'nsr.code',
            'roleSecondary' => 'nsrr.name',
        ];

        $joinTables = array(
            'e' => array('e'),
            's' => array('e','s'),
            'c' => array('e','s','c'),
            'nsr' => array('nsr'),
            'nsrr' => array('nsrr'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(NetworkVirtualSystem::class, 'ns');
        $query->select('ns'); // Seleccionamos el objeto completo

        // Filtro por estado activo/inactivo
        if ($request->get('active') === "false") {
            $query->andWhere('ns.active = FALSE');
        } else {
            $query->andWhere('ns.active = TRUE');
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
                                        // en la tcmdb es nd.environment
                                        $query->join('ns.environment', 'e');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c');
                                        break;
                                    case 'nsr':
                                        $query->leftJoin('ns.role', 'nsr');
                                        break;
                                    case 'nsrr':
                                        $query->leftJoin('ns.roleSecondary', 'nsrr');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'e':
                                        // en la tcmdb es nd.environment
                                        $query->join('ns.environment', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.active=TRUE');
                                        break;
                                    case 'c':
                                        $query->join('s.client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.active=TRUE');
                                        break;
                                    case 'nsr':
                                        $query->leftJoin('ns.role', 'nsr');
                                        break;
                                    case 'nsrr':
                                        $query->leftJoin('ns.roleSecondary', 'nsrr');
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

    #[Route('/select2/data', name: 'app_select2networkVirtualSystemsData')]
    public function select2networkVirtualSystemsData(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        $limit = 10;
        $term = $request->query->get('search');

        $em = $doctrine->getManager();

        $query = $em
            ->getRepository(NetworkVirtualSystem::class)
            ->createQueryBuilder('ns')
            ->join('ns.networkDevice', 'nd')
            ->join('nd.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c')
            ->where('ns.name LIKE :val')
            ->andWhere('ns.active = TRUE')
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
            $element['text'] = ($item->getNetworkDevice() ? $item->getNetworkDevice()->getName() . ': ' : '') . $item->getName();
            $response['results'][] = $element;
        }
        
        if((int) $response['last_page'] == (int) $page || (int) $response['last_page'] == 0){
            $response['pagination']['more'] = false;
        } else {
            $response['pagination']['more'] = true;
        }
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/select2/data/{id}', name: 'app_select2networkVirtualSystemsDataId')]
    public function select2networkVirtualSystemsDataId(NetworkVirtualSystem $networkVirtualSystem): Response
    {
        $response['id'] = $networkVirtualSystem->getId();
        $response['text'] = ($networkVirtualSystem->getNetworkDevice() ? $networkVirtualSystem->getNetworkDevice()->getName() . ': ' : null) . $networkVirtualSystem->getName();
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_networkVirtualSystemsDetail')]
    public function networkVirtualSystemsDetail(NetworkVirtualSystem $networkVirtualSystem): Response
    {
        //$this->denyAccessUnlessGranted('view', $networkVirtualSystem);
        return $this->render('_detail.html.twig', [
            'pageTitle' => 'Network Virtual System' . ' ' . $networkVirtualSystem->getName(),
            'pageView' => $this->generateUrl('app_networkVirtualSystemsView', ['id' => $networkVirtualSystem->getId()]),
            'auditView' => $this->generateUrl('app_networkVirtualSystemsAudit', ['id' => $networkVirtualSystem->getId()]),
            'object'  => $networkVirtualSystem,
        ]);
    }

    #[Route('/{id}/view', name: 'app_networkVirtualSystemsView')]
    public function networkVirtualSystemsView(NetworkVirtualSystem $networkVirtualSystem): Response
    {
        //$this->denyAccessUnlessGranted('view', $networkVirtualSystem);
        return $this->render('network/virtual_systems/_networkVirtualSystemView.html.twig', [
            'pageTitle' => 'Network Virtual System' . ' ' . $networkVirtualSystem->getName(),
            'object'  => $networkVirtualSystem,
        ]);
    }

    #[Route('/{id}/audit', name: 'app_networkVirtualSystemsAudit')]
    public function networkVirtualSystemsAudit(NetworkVirtualSystem $networkVirtualSystem, Request $request): Response
    {
        //$this->denyAccessUnlessGranted('view', $networkVirtualSystem);
        $changes = array();
        $logs = $this->networkVirtualSystemLogRepository->findBy(array('idNetworkVirtualSystem' => $networkVirtualSystem->getId()), array('DateLOG' => 'ASC'));

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
            }
        }

        // Create an array to hold the 'date' values
        $dates = array();
        foreach ($changes as $key => $row) {
            $dates[$key] = strtotime($row['DateLOG']->format('Y-m-d H:i:s'));
        }
        array_multisort($dates, SORT_ASC, $changes);

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
