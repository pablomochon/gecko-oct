<?php

namespace App\Controller;

ini_set('memory_limit', -1);

use App\Entity\Client;
use App\Repository\ClientLOGRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\ClientRepository;
use App\Form\ClientType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;



#[Route('/clients')]
class ClientsController extends AbstractController
{
    public $basePath = '';

    public function __construct(
        KernelInterface $appKernel,
        private ClientRepository $clientRepository,
        private ClientLOGRepository $clientLOGRepository,
    ) {
        $this->basePath = $appKernel->getProjectDir();
    }

    #[Route('/', name: 'app_clients', methods: ['GET'])]
    public function index(TranslatorInterface $translator): Response
    {
        return $this->render('clients/index.html.twig', [
            'pageTitle'  => $translator->trans('Clients'),
            'templatemtime' => str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/clients/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_clientsPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine): Response
    {
        $params = json_decode($request->query->get('params'), true);

        $sortingBy = isset($params['sort']) ? $params['sort'] : [];

        $columns = array(
            'id' => 'c.id',
            'name' => 'c.name',
            'code' => 'c.code',
            'services' => 's.name',
        );

        $joinTables = array(
            's' => array('s'),
        );

        $em = $doctrine->getManager();
        $table = array();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $query = $em->createQueryBuilder();
        $query->from(Client::class, 'c');

        if ($request->get('active') === "false") {
            $query->where('c.active=FALSE');
        } else {
            $query->where('c.active=TRUE');
        }

        foreach ($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }

        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach ($columnVisibility as $columnKey => $column) {
            if (key($column) == 'name' || key($column) == 'id') {
                $columnVisibility[$columnKey][key($column)] = true;
            }
        }
        foreach ($columnVisibility as $column) {
            if ($column[key($column)]) {
                $query->addSelect($columns[key($column)] . ' AS ' . key($column));

                // Se encuentra en las tablas que "necesitan" joins, pero actualmente no está el join en la query
                if (in_array(explode('.', $columns[key($column)])[0], array_keys($joinTables)) && !in_array(explode('.', $columns[key($column)])[0], $joinedTables)) {
                    foreach ($joinTables[explode('.', $columns[key($column)])[0]] as $tableToJoin) {
                        if (!in_array($tableToJoin, $joinedTables)) {
                            if ($request->get('active') === "false") {
                                switch ($tableToJoin) {
                                    case 's':
                                        $query->leftJoin('c.services', 's');
                                        break;
                                }
                            } else {
                                switch ($tableToJoin) {
                                    case 's':
                                        $query->leftJoin('c.services', 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.active=TRUE');
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
                    if ($column[key($column)] && key($column) != 'id' && in_array(key($column), array('services'))) {
                        switch (key($column)) {
                            default:
                                $table[$result['id']][key($column)] .= ', ' . $result[key($column)];
                        }
                    }
                }
            } else {
                $table[$result['id']] = array(
                    'id' => $result['id'],
                );
                foreach ($columnVisibility as $column) {
                    if ($column[key($column)] && key($column) != 'id') {
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
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'app_clientsDetail')]
    public function clientsDetail(Client $client, Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(
            ClientType::class,
            $client,
            [
                'action' => $this->generateUrl('app_clientsDetail', ['id' => $client->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('edit', $client)) {
                $this->addFlash('error', "You don't have access to edit element " . $client->getName());
                return $this->redirectToRoute('app_clients');
            }

            $entityManager = $doctrine->getManager();
            $entityManager->flush();

            $this->addFlash('success', "Element " . $client->getName() . " updated");
            return $this->redirectToRoute('app_clients');
        }

        return $this->render('_detail.html.twig', [
            'pageTitle' => $translator->trans('Client') . ' ' . $client->getName(),
            'pageView' => $this->generateUrl('app_clientsView', ['id' => $client->getId()]),
            'auditView' => $this->generateUrl('app_clientsAudit', ['id' => $client->getId()]),
            'form'  => $form->createView(),
            'object'  => $client,
        ]);
    }

    #[Route('/{id}/view', name: 'app_clientsView')]
    public function clientsView(Client $client, TranslatorInterface $translator): Response
    {
        return $this->render('clients/_clientsView.html.twig', [
            'pageTitle' => $translator->trans('Client') . ' ' . $client->getName(),
            'object'  => $client,
        ]);
    }

    #[Route('/{id}/audit', name: 'app_clientsAudit')]
    public function clientsAudit(Client $client, Request $request): Response
    {
        $changes = array();
        $logs = $this->clientLOGRepository->findBy(array('idClient' => $client->getId()), array('DateLOG' => 'ASC'));

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
                    'field' => 'Code',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getCode(),
                    'after' => $log->getCode(),
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
                if ($log->getCode() !== $logs[$key - 1]->getCode()) {
                    $changes[] = array(
                        'field' => 'Code',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getCode(),
                        'after' => $log->getCode(),
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
