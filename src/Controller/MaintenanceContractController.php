<?php

namespace App\Controller;

use App\Entity\MaintenanceContract;
use App\Form\MaintenanceContractType;
use App\Repository\MaintenanceContractLOGRepository;
use App\Repository\MaintenanceContractRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;


#[Route('/contracts')]
class MaintenanceContractController extends AbstractController
{
    public $basePath = '';

    public function __construct(
        KernelInterface $appKernel,
        private MaintenanceContractRepository $maintenanceContractRepository,
        private MaintenanceContractLOGRepository $maintenanceContractLOGRepository,
    ) {
        $this->basePath = $appKernel->getProjectDir();
    }

    #[Route('/', name: 'app_contracts', methods: ['GET'])]
    public function index(TranslatorInterface $translator): Response
    {
        return $this->render('maintenanceContract/index.html.twig', [
            'pageTitle'  => $translator->trans('Contracts'),
            'templatemtime' => str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/maintenanceContract/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_contractsPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine): Response
    {
        $params = json_decode($request->query->get('params'), true);
        $sortingBy = isset($params['sort']) ? $params['sort'] : [];

        // COLUMNS CON NETWORK DEVICES
        $columns = array(
            'id' => 'mc.id',
            'name' => 'mc.name',
            'startDate' => 'mc.startDate',
            'endDate' => 'mc.endDate',
            'manufacturer' => 'mc.manufacturer',
            'provider' => 'mc.provider',
            'status' => 'mc.status',
            'notes' => 'mc.notes',
            'networkElement' => 'nd.name',
        );

        $joinTables = array(
            'nd' => array('nd'),
        );

        // INICIALIZAR TABLE
        $table = array();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(MaintenanceContract::class, 'mc');

        if ($request->get('active') === "false") {
            $query->where('mc.active=FALSE');
        } else {
            $query->where('mc.active=TRUE');
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
                                    case 'nd':
                                        $query->leftJoin('mc.networkDevices', 'nd');
                                        break;
                                }
                            } else {
                                switch ($tableToJoin) {
                                    case 'nd':
                                        $query->leftJoin('mc.networkDevices', 'nd', \Doctrine\ORM\Query\Expr\Join::WITH, 'mc.active=TRUE');
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

        // COLUMNS CON NETWORK VIRTUAL SYSTEMS
        $columns = array(
            'id' => 'mc.id',
            'name' => 'mc.name',
            'startDate' => 'mc.startDate',
            'endDate' => 'mc.endDate',
            'manufacturer' => 'mc.manufacturer',
            'provider' => 'mc.provider',
            'status' => 'mc.status',
            'notes' => 'mc.notes',
            'networkElement' => 'ns.name',
        );

        $joinTables = array(
            'ns' => array('ns'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(MaintenanceContract::class, 'mc');

        if ($request->get('active') === "false") {
            $query->where('mc.active=FALSE');
        } else {
            $query->where('mc.active=TRUE');
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
                                    case 'ns':
                                        $query->leftJoin('mc.networkVirtualSystems', 'ns');
                                        break;
                                }
                            } else {
                                switch ($tableToJoin) {
                                    case 'ns':
                                        $query->leftJoin('mc.networkDevices', 'ns', \Doctrine\ORM\Query\Expr\Join::WITH, 'mc.active=TRUE');
                                        break;
                                }
                            }
                            $joinedTables[] = $tableToJoin;
                        }
                    }
                }
            }
        }

        // Crear el $table en base al result
        foreach ($query->getQuery()->getScalarResult() as $result) {
            foreach ($columnVisibility as $column) {
                if ($column[key($column)] && isset($columns[key($column)])) {
                    switch (key($column)) {
                        default:
                            $table[$result['id']][key($column)] = $result[key($column)];
                    }
                }
            }
        }

        return new Response(
            json_encode(array_values($table)),
            Response::HTTP_OK
        );
    }

    #[Route('/add', name: 'app_contractsAdd')]
    public function contractsAdd(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(MaintenanceContractType::class, null, [
            'action' => $this->generateUrl('app_contractsAdd'),
        ]);
        $form->handleRequest($request);

        // Check if the form is submitted
        if ($form->isSubmitted()) {

            $errors = $validator->validate($form->getData());

            if (count($errors) === 0) {

                $entityManager = $doctrine->getManager();
                $contract = $form->getData();
                $entityManager->persist($contract);
                $entityManager->flush();

                $this->addFlash('success', sprintf('New contract added: %s', $contract->getName()));
                return $this->redirectToRoute('app_contracts');
            } else {
                
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_contracts');
            }
        }

        return $this->render('maintenanceContract/_contractsForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bulkEdit', name: 'app_contractsBulkEdit')]
    public function contractsBulkEdit(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(MaintenanceContractType::class, null, [
            'action' => $this->generateUrl('app_contractsBulkEdit'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $data = $form->getData();
            $entityManager = $doctrine->getManager();
            $ids = explode(',', $request->get('objectsToEdit'));
            $elements = $this->maintenanceContractRepository->findBy(['id' => $ids]);

            foreach ($elements as $element) {

                if (!$this->isGranted('edit', $element) || !$this->isGranted('ROLE_DATA_UPLOADER')) {
                    $message = "You don't have access to edit element " . $element->getName();
                    $this->addFlash('error', $message);
                    continue;
                }

                foreach ($request->get('client') as $field => $value) {
                    if ($field != '_token') {
                        try {
                            $method = 'get' . ucfirst($field);
                            $value = $data->$method();

                            $method = 'set' . ucfirst($field);
                            $element->$method($value);
                        } catch (\Throwable $e) {
                            $this->addFlash('error', $e->getMessage());
                        }
                    }
                }

                $errors = $validator->validate($element);

                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', (string) $error);
                    }
                } else {
                    $entityManager->persist($element);
                    $entityManager->flush();
                }
            }
        }

        return new JsonResponse('OK', Response::HTTP_OK);
    }

    #[Route('/select2/data', name: 'app_select2contractsData')]
    public function select2contractsData(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        $limit = 10;
        $term = $request->query->get('search');

        $em = $doctrine->getManager();

        $query = $em
            ->getRepository(MaintenanceContract::class)
            ->createQueryBuilder('c')
            ->where('c.name LIKE :val')
            ->andWhere('c.active = TRUE')
            ->setParameter('val', "%$term%");

        if ($limit === true) {
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

        foreach ($result as $item) {
            $element = array();
            $element['id'] = $item->getId();
            $element['text'] = $item->getName();
            $response['results'][] = $element;
        }

        if ((int) $response['last_page'] == (int) $page || (int) $response['last_page'] == 0) {
            $response['pagination']['more'] = false;
        } else {
            $response['pagination']['more'] = true;
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/select2/data/{id}', name: 'app_select2contractsDataId')]
    public function select2contractsDataId(MaintenanceContract $client): Response
    {
        $response['id'] = $client->getId();
        $response['text'] = $client->getName();

        return new JsonResponse($response, Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'app_contractsDetail')]
    public function contractsDetail(MaintenanceContract $contract, Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(
            MaintenanceContractType::class,
            $contract,
            [
                'action' => $this->generateUrl('app_contractsDetail', ['id' => $contract->getId()]),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('edit', $contract)) {
                $this->addFlash('error', "You don't have access to edit element " . $contract->getName());
                return $this->redirectToRoute('app_contracts');
            }

            $entityManager = $doctrine->getManager();
            $entityManager->flush();

            $this->addFlash('success', "Element " . $contract->getName() . " updated");
            return $this->redirectToRoute('app_contracts');
        }

        return $this->render('_detail.html.twig', [
            'pageTitle' => $translator->trans('Contract') . ' ' . $contract->getName(),
            'pageView' => $this->generateUrl('app_contractsView', ['id' => $contract->getId()]),
            'auditView' => $this->generateUrl('app_contractsAudit', ['id' => $contract->getId()]),
            'form'  => $form->createView(),
            'object'  => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contractsEdit')]
    public function contractsEdit(MaintenanceContract $maintenanceContract, Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(
            MaintenanceContractType::class,
            $maintenanceContract,
            [
                'action' => $this->generateUrl('app_contractsEdit', ['id' => $maintenanceContract->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());

            if (count($errors) === 0) {
                $entityManager = $doctrine->getManager();
                $maintenanceContract = $form->getData();
                $entityManager->persist($maintenanceContract);
                $entityManager->flush();

                $this->addFlash('success', "Maintenance contract " . $maintenanceContract->getName() . " updated");
                return $this->redirectToRoute('app_contracts');
            } else {
                /*
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_contracts');
                */
                return $this->render('maintenanceContract/_contractsForm.html.twig', [
                    'form'  => $form->createView(),
                    'object'  => $maintenanceContract,
                ]);
            }
        }

        return $this->render('maintenanceContract/_contractsForm.html.twig', [
            'form'  => $form->createView(),
            'object'  => $maintenanceContract,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_contractsDelete')]
    public function contractsDelete(MaintenanceContract $maintenanceContract, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $entityManager->remove($maintenanceContract);
        $entityManager->flush();

        $this->addFlash('success', "Maintenance contract " . $maintenanceContract->getName() . " removed");
        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/{id}/view', name: 'app_contractsView')]
    public function contractsView(MaintenanceContract $maintenanceContract, TranslatorInterface $translator): Response
    {
        return $this->render('maintenanceContract/_contractsView.html.twig', [
            'pageTitle' => $translator->trans('Contract') . ' ' . $maintenanceContract->getName(),
            'object'  => $maintenanceContract,
        ]);
    }

    #[Route('/{id}/audit', name: 'app_contractsAudit')]
    public function contractsAudit(MaintenanceContract $contract, Request $request): Response
    {
        $changes = array();
        $logs = $this->maintenanceContractLOGRepository->findBy(array('idMaintenanceContract' => $contract->getId()), array('DateLOG' => 'ASC'));

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
                    'field' => 'startDate',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getStartDate(),
                    'after' => $log->getStartDate()->format('Y-m-d H:i:s'),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'endDate',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getEndDate(),
                    'after' => $log->getEndDate()->format('Y-m-d H:i:s'),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'manufacter',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getManufacturer(),
                    'after' => $log->getManufacturer(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'provider',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getProvider(),
                    'after' => $log->getProvider(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'status',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->isStatus(),
                    'after' => $log->isStatus(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'cost',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getCost(),
                    'after' => $log->getCost(),
                    'action' => $log->getAction(),
                    'DateLOG' => $log->getDateLOG(),
                    'UserLOGname' => $log->getUserLOG()->getName(),
                    'UserLOGuser' => $log->getUserLOG()->getUsername()
                );
                $changes[] = array(
                    'field' => 'notes',
                    'before' => ($key === 0) ? '' : $logs[$key - 1]->getNotes(),
                    'after' => $log->getNotes(),
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
                if ($log->getStartDate() !== $logs[$key - 1]->getStartDate()) {
                    $changes[] = array(
                        'field' => 'startDate',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getStartDate()->format('Y-m-d H:i:s'),
                        'after' => $log->getStartDate()->format('Y-m-d H:i:s'),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getEndDate() !== $logs[$key - 1]->getEndDate()) {
                    $changes[] = array(
                        'field' => 'startDate',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getEndDate()->format('Y-m-d H:i:s'),
                        'after' => $log->getEndDate()->format('Y-m-d H:i:s'),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getManufacturer() !== $logs[$key - 1]->getManufacturer()) {
                    $changes[] = array(
                        'field' => 'manufacter',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getManufacturer(),
                        'after' => $log->getManufacturer(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getProvider() !== $logs[$key - 1]->getProvider()) {
                    $changes[] = array(
                        'field' => 'provider',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getProvider(),
                        'after' => $log->getProvider(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->isStatus() !== $logs[$key - 1]->isStatus()) {
                    $changes[] = array(
                        'field' => 'status',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->isStatus(),
                        'after' => $log->isStatus(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getCost() !== $logs[$key - 1]->getCost()) {
                    $changes[] = array(
                        'field' => 'cost',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getCost(),
                        'after' => $log->getCost(),
                        'action' => $log->getAction(),
                        'DateLOG' => $log->getDateLOG(),
                        'UserLOGname' => $log->getUserLOG()->getName(),
                        'UserLOGuser' => $log->getUserLOG()->getUsername()
                    );
                }
                if ($log->getNotes() !== $logs[$key - 1]->getNotes()) {
                    $changes[] = array(
                        'field' => 'notes',
                        'before' => ($key === 0) ? '' : $logs[$key - 1]->getNotes(),
                        'after' => $log->getNotes(),
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
