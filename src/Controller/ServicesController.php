<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceLOGRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\ServiceRepository;
use App\Repository\NetworkDeviceRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;

#[Route('/services')]
class ServicesController extends AbstractController
{
    private $serviceLogRepository;
    var $basePath = '';

    public function __construct(KernelInterface $appKernel
        , NetworkDeviceRepository $networkDeviceRepository
        , ServiceRepository $serviceRepository
        , ServiceLOGRepository $serviceLogRepository)
    {
        $this->basePath = $appKernel->getProjectDir();
        $this->networkDeviceRepository = $networkDeviceRepository;
        $this->serviceRepository = $serviceRepository;
        $this->serviceLogRepository = $serviceLogRepository;
    }

    #[Route('/', name: 'app_services', methods: ['GET'])]
    public function index(Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator): Response
    {
        return $this->render('services/index.html.twig', [
            'pageTitle'  => $translator->trans('Services'),
            'templatemtime' => str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/services/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_servicesPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator): Response
    {
        $params = json_decode($request->query->get('params'), true);

        $sortingBy = isset($params['sort']) ? $params['sort'] : [];
        
        $columns = array(
            'id' => 's.id',
            'name' => 's.name',
            'tcosrv' => 's.tcosrv',
            'pep' => 's.pep',
            'description' => 's.description',
            'client' => 'c.name',
            'environments' => 'e.name',
        );

        $joinTables = array(
            'c' => array('c'),
            'e' => array('e'),
        );

        $em = $doctrine->getManager();
        $table = array();
        /*
        $query = $em
            ->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->select(array('s.id'));
        */

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $query = $em->createQueryBuilder();
        $query->from(Service::class, 's');
        
        if($request->get('active') === "false") {
            $query->where('s.active=FALSE');
        } else {
            $query->where('s.active=TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }

        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        //Para filtrar por cliente
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'client' || key($column) == 'id') {
                $columnVisibility[$columnKey][key($column)] = true;
            }
        }
        foreach($columnVisibility as $column) {
            if($column[key($column)]) {
                $query->addSelect($columns[key($column)] . ' AS ' . key($column));

                // Se encuentra en las tablas que "necesitan" joins, pero actualmente no está el join en la query
                if(in_array(explode('.', $columns[key($column)])[0], array_keys($joinTables)) && !in_array(explode('.', $columns[key($column)])[0], $joinedTables)) {
                    foreach($joinTables[explode('.', $columns[key($column)])[0]] as $tableToJoin) {
                        if(!in_array($tableToJoin, $joinedTables)) {
                            if($request->get('active') === "false") {
                                switch($tableToJoin) {
                                    case 'c':
                                        $query->join('s.client', 'c');
                                        break;
                                    case 'e':
                                        $query->leftJoin('s.environments', 'e');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'c':
                                        $query->join('s.client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.active=TRUE');
                                        break;
                                    case 'e':
                                        $query->leftJoin('s.environments', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
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
                    if($column[key($column)] && key($column) != 'id' && in_array(key($column), array('environments'))) {
                        switch(key($column)) {
                            default:
                                $table[$result['id']][key($column)] .= ', ' . $result[key($column)];
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
                            case 'client':
                                $table[$result['id']][key($column)] = $result[key($column)] ? $result[key($column)] : '';
                                break;
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

    #[Route('/select2/data', name: 'app_select2servicesData')]
    public function select2servicesData(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        $limit = 10;
        $term = $request->query->get('search');

        $em = $doctrine->getManager();

        $query = $em
            ->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->join('s.client', 'c')
            ->where('s.name LIKE :val')
            ->andWhere('s.active = TRUE')
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
            $element['text'] = $item->getClient()->getName() . ': ' . $item->getName();
            $response['results'][] = $element;
        }
        
        if((int) $response['last_page'] == (int) $page || (int) $response['last_page'] == 0){
            $response['pagination']['more'] = false;
        } else {
            $response['pagination']['more'] = true;
        }
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/select2/data/{id}', name: 'app_select2servicesDataId')]
    public function select2servicesDataId(Service $service): Response
    {
        $response['id'] = $service->getId();
        $response['text'] = $service->getClient()->getName() . ': ' . $service->getName();
        
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_servicesDetail')]
    public function servicesDetail(Service $service, Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator): Response
    {        
        return $this->render('_detail.html.twig', [
            'pageTitle' => $translator->trans('Service') . ' ' . $service->getName(),
            'pageView' => $this->generateUrl('app_servicesView', [ 'id' => $service->getId() ]),
            'auditView' => $this->generateUrl('app_servicesAudit', [ 'id' => $service->getId() ]),
            'object'  => $service,
        ]);
    }

    #[Route('/{id}/view', name: 'app_servicesView')]
    public function servicesView(Service $service, TranslatorInterface $translator): Response
    {        
        return $this->render('services/_servicesView.html.twig', [
            'pageTitle' => $translator->trans('Service') . ' ' . $service->getName(),
            'object'  => $service,
            // 'countNetworkDevices' => $this->networkDeviceRepository->countByServiceActive($service->getId()),
        ]);
    }

    #[Route('/{id}/audit', name: 'app_servicesAudit')]
    public function servicesAudit(Service $service, Request $request): Response
    {
        $changes = array();
        $logs = $this->serviceLogRepository->findBy(array('idService' => $service->getId()), array('DateLOG' => 'ASC'));

        foreach($logs as $key => $log) {
            if($key === 0) {
                $changes[] = array('field' => 'Name'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getName()
                                , 'after' => $log->getName()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'TCOSRV'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getTcosrv()
                                , 'after' => $log->getTcosrv()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'PeP'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getPep()
                                , 'after' => $log->getPep()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'Description'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getDescription()
                                , 'after' => $log->getDescription()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'Client'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getClient()->getName()
                                , 'after' => $log->getClient()->getName()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'Active'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->isActive()
                                , 'after' => $log->isActive()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
            } else {
                if($log->getName() !== $logs[$key-1]->getName()) {
                    $changes[] = array('field' => 'Name'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getName()
                                    , 'after' => $log->getName()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getTcosrv() !== $logs[$key-1]->getTcosrv()) {
                    $changes[] = array('field' => 'TCOSRV'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getTcosrv()
                                    , 'after' => $log->getTcosrv()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getPep() !== $logs[$key-1]->getPep()) {
                    $changes[] = array('field' => 'PeP'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getPep()
                                    , 'after' => $log->getPep()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getDescription() !== $logs[$key-1]->getDescription()) {
                    $changes[] = array('field' => 'Description'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getDescription()
                                    , 'after' => $log->getDescription()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getClient() !== $logs[$key-1]->getClient()) {
                    $changes[] = array('field' => 'Client'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getClient()->getName()
                                    , 'after' => $log->getClient()->getName()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->isActive() !== $logs[$key-1]->isActive()) {
                    $changes[] = array('field' => 'Active'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->isActive()
                                    , 'after' => $log->isActive()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
            }
        }

        $changesOrdered = array();
        foreach(array_reverse($changes) as $change) {
            $changesOrdered[$change['DateLOG']->format('Y-m-d H:i:s')][$change['UserLOGuser']][] = $change;
        }

        if($request->get('format') == 'application/json') {
            return new JsonResponse($changesOrdered, Response::HTTP_OK);
        } else {
            return $this->render('_auditViewer.html.twig', [
                'changes'  => $changesOrdered,
            ]);
        }
    }
}
