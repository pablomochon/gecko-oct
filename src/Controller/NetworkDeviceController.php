<?php

namespace App\Controller;

/* IMPORTANTE PARA OBTENER TODOS LOS DATOS */

ini_set('memory_limit', -1);

use App\Entity\NetworkDevice;
use App\Repository\NetworkDeviceRepository;
use App\Repository\NetworkDeviceLOGRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/network/devices')]
class NetworkDeviceController extends AbstractController
{
    public $basePath = '';

    public function __construct(
        KernelInterface $appKernel,
        private NetworkDeviceRepository $networkDeviceRepository,
        private NetworkDeviceLOGRepository $networkDeviceLogRepository,
    ) {
        $this->basePath = $appKernel->getProjectDir();
    }

    #[Route('/', name: 'app_network_device', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('network/devices/index.html.twig', [
            'pageTitle'  => 'Network Device',
            'templatemtime' => \str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/network/devices/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_networkDevicesPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine): Response
    {
        $params = json_decode($request->query->get('params'), true);

        $sortingBy = isset($params['sort']) ? $params['sort'] : [];

        $columns = array(
            'id' => 'nd.id',
            'name' => 'nd.name',
            'environmentName' => 'e.name',
            'serviceName' => 's.name',
            'clientName' => 'c.name',
            'serialNumber' => 'nd.serialNumber',
            // 'secondName' => 'nd.secondName',
            // 'comments' => 'nd.comments',
            // 'networkVirtualSystems' => 'ns.name',
            // 'networkInterfaces' => 'ni.name',
        );

        $joinTables = array(
            'p' => array('p'),
            'm' => array('p', 'm'),
            'e' => array('e'),
            's' => array('e', 's'),
            'c' => array('e', 's', 'c'),
        );

        // Inicializar la variable $table como un array vacío
        $table = array();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(NetworkDevice::class, 'nd');

        if ($request->get('active') === "false") {
            $query->andWhere('nd.active=FALSE');
        } else {
            $query->andWhere('nd.active=TRUE');
        }

        foreach ($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }

        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        //Para filtrar por cliente
        foreach ($columnVisibility as $columnKey => $column) {
            if (key($column) == 'clientName' || key($column) == 'id') {
                $columnVisibility[$columnKey][key($column)] = true;
            }
        }

        foreach ($columnVisibility as $column) {
            if ($column[key($column)] && isset($columns[key($column)])) {
                $query->addSelect($columns[key($column)] . ' AS ' . key($column));

                // Se encuentra en las tablas que "necesitan" joins, pero actualmente no está el join en la query
                if (in_array(explode('.', $columns[key($column)])[0], array_keys($joinTables)) && !in_array(explode('.', $columns[key($column)])[0], $joinedTables)) {
                    foreach ($joinTables[explode('.', $columns[key($column)])[0]] as $tableToJoin) {
                        if (!in_array($tableToJoin, $joinedTables)) {
                            if ($request->get('active') === "false") {
                                switch ($tableToJoin) {
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
                                switch ($tableToJoin) {
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

        foreach ($query->getQuery()->getScalarResult() as $result) {
            if (isset($table[$result['id']])) {
                foreach ($columnVisibility as $column) {
                    if ($column[key($column)] && key($column) != 'id' && isset($columns[key($column)]) && in_array(key($column), array('networkVirtualSystems', 'networkInterfaces', 'ips', 'networkStackCIGroup', 'networkModulesCIGroup'))) {
                        switch (key($column)) {
                            default:
                                if (strpos($table[$result['id']][key($column)], $result[key($column)]) === false) {
                                    $table[$result['id']][key($column)] .= ', ' . $result[key($column)];
                                }
                        }
                    }
                }
            } else {
                $table[$result['id']] = array(
                    'id' => $result['id'],
                );
                foreach ($columnVisibility as $column) {
                    if ($column[key($column)] && key($column) != 'id' && isset($columns[key($column)])) {
                        switch (key($column)) {
                            default:
                                $table[$result['id']][key($column)] = $result[key($column)];
                        }
                    }
                }
            }
        }

        return new Response(
            json_encode(array_values($table)),
            Response::HTTP_OK,
        );
    }

    #[Route('/select2/data', name: 'app_select2networkDeviceData')]
    public function select2networkDeviceData(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        $limit = 10;
        $term = $request->query->get('search');

        $em = $doctrine->getManager();

        $query = $em
            ->getRepository(NetworkDevice::class)
            ->createQueryBuilder('nd')
            ->join('nd.environment', 'e')
            ->join('e.service', 's')
            ->join('s.client', 'c')
            ->where('nd.name LIKE :val OR nd.serialNumber LIKE :val')
            ->andWhere('nd.active = TRUE')
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
            $element['text'] = $item->getName() . ' (SN: ' . $item->getSerialNumber() . ')';
            $response['results'][] = $element;
        }
        
        if((int) $response['last_page'] == (int) $page || (int) $response['last_page'] == 0){
            $response['pagination']['more'] = false;
        } else {
            $response['pagination']['more'] = true;
        }
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/select2/data/{id}', name: 'app_select2networkDeviceDataId')]
    public function select2networkDeviceDataId(NetworkDevice $networkDevice): Response
    {
        $response['id'] = $networkDevice->getId();
        $response['text'] = $networkDevice->getName() . ' (SN: ' . $networkDevice->getSerialNumber() . ')';
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_networkDeviceDetail')]
    public function networkDevicesDetail(NetworkDevice $networkDevice): Response
    {
        //$this->denyAccessUnlessGranted('view', $networkDevice);
        return $this->render('_detail.html.twig', [
            'pageTitle' => 'Network Device' . ' ' . $networkDevice->getName(),
            'pageView' => $this->generateUrl('app_networkDevicesView', ['id' => $networkDevice->getId()]),
            'auditView' => $this->generateUrl('app_networkDevicesAudit', ['id' => $networkDevice->getId()]),
            'object'  => $networkDevice,
        ]);
    }

    #[Route('/{id}/view', name: 'app_networkDevicesView')]
    public function networkDevicesView(NetworkDevice $networkDevice): Response
    {
        //$this->denyAccessUnlessGranted('view', $networkDevice);
        return $this->render('network/devices/_networkDeviceView.html.twig', [
            'pageTitle' => 'Network Device' . ' ' . $networkDevice->getName(),
            'object'  => $networkDevice,
        ]);
    }

    #[Route('/{id}/audit', name: 'app_networkDevicesAudit')]
    public function networkDevicesAudit(NetworkDevice $networkDevice, Request $request): Response
    {
        //$this->denyAccessUnlessGranted('view', $networkDevice);
        $changes = array();
        $logs = $this->networkDeviceLogRepository->findBy(array('idNetworkDevice' => $networkDevice->getId()), array('DateLOG' => 'ASC'));

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
                    'field' => 'SerialNumber',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getSerialNumber(),
                    'after' => $log->getSerialNumber(),
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
                    'field' => 'Environment',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getEnvironment()->getName(),
                    'after' => $log->getEnvironment() ? $log->getEnvironment()->getName() : null,
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
                if ($log->getEnvironment() !== $logs[$key - 1]->getEnvironment()) {
                    $changes[] = array(
                        'field' => 'Environment',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getEnvironment()->getName(),
                        'after' => $log->getEnvironment()->getName(),
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
                if ($log->getSerialNumber() !== $logs[$key - 1]->getSerialNumber()) {
                    $changes[] = array(
                        'field' => 'SerialNumber',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getSerialNumber(),
                        'after' => $log->getSerialNumber(),
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
