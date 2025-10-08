<?php

namespace App\Controller;
ini_set('memory_limit', -1);

use App\Entity\Environment;
use App\Repository\EnvironmentLOGRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\EnvironmentRepository;
use App\Repository\NetworkDeviceRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;

#[Route('/environments')]
class EnvironmentsController extends AbstractController
{
    var $basePath = '';
    
    public function __construct(
        KernelInterface $appKernel,
        private NetworkDeviceRepository $networkDeviceRepository,
        private EnvironmentRepository $environmentRepository,
        private EnvironmentLOGRepository $environemntLogRepository,
        )
    {
        $this->basePath = $appKernel->getProjectDir();
    }

    #[Route('/', name: 'app_environments', methods: ['GET'])]
    public function index(TranslatorInterface $translator): Response
    {
        return $this->render('environments/index.html.twig', [
            'pageTitle'  => $translator->trans('Environments'),
            'templatemtime' => str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/environments/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_environmentsPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator): Response
    {
        $params = json_decode($request->query->get('params'), true);

        $sortingBy = isset($params['sort']) ? $params['sort'] : [];
        
        $columns = array(
            'id' => 'e.id',
            'name' => 'e.name',
            'type' => 'e.type',
            'service' => 's.name',
            'client' => 'c.name',
        );

        $joinTables = array(
            's' => array('s'),
            'c' => array('s', 'c'),
            't' => array('t'),
        );

        $em = $doctrine->getManager();
        $table = array();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $query = $em->createQueryBuilder();
        $query->from(Environment::class, 'e');
        
        if($request->get('active') === "false") {
            $query->where('e.active=FALSE');
        } else {
            $query->where('e.active=TRUE');
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
                                        $query->join('s.cleint', 'c');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'c':
                                        $query->join('s.client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.active=TRUE');
                                        break;
                                    case 's':
                                        $query->join('e.service', 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.active=TRUE');
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
                    if($column[key($column)] && key($column) != 'id' && in_array(key($column), array('ip'))) {
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

    #[Route('/{id}', name: 'app_environmentsDetail')]
    public function environmentsDetail(Environment $environment, Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator): Response
    {        
        return $this->render('_detail.html.twig', [
            'pageTitle' => $translator->trans('Environment') . ' ' . $environment->getName(),
            'pageView' => $this->generateUrl('app_environmentsView', [ 'id' => $environment->getId() ]),
            'auditView' => $this->generateUrl('app_environmentsAudit', [ 'id' => $environment->getId() ]),
            'object'  => $environment,
        ]);
    }

    #[Route('/{id}/view', name: 'app_environmentsView')]
    public function environmentsView(Environment $environment, TranslatorInterface $translator): Response
    {        
        return $this->render('environments/_environmentsView.html.twig', [
            'pageTitle' => $translator->trans('Environment') . ' ' . $environment->getName(),
            'object'  => $environment,
        ]);
    }

    #[Route('/{id}/audit', name: 'app_environmentsAudit')]
    public function environmentsAudit(Environment $environment, Request $request): Response
    {
        $changes = array();
        $logs = $this->environemntLogRepository->findBy(array('idEnvironment' => $environment->getId()), array('DateLOG' => 'ASC'));

        foreach($logs as $key => $log) {
            if($key === 0) {
                $changes[] = array('field' => 'Name'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getName()
                                , 'after' => $log->getName()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'Type'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getType()
                                , 'after' => $log->getType()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'Service'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getService()->getName()
                                , 'after' => $log->getService()->getName()
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
                if($log->getType() !== $logs[$key-1]->getType()) {
                    $changes[] = array('field' => 'Type'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getType()
                                    , 'after' => $log->getType()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getService() !== $logs[$key-1]->getService()) {
                    $changes[] = array('field' => 'Service'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getService()->getName()
                                    , 'after' => $log->getService()->getName()
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
