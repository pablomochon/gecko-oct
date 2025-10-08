<?php

namespace App\Controller;

use App\Entity\ActivityType;
use App\Repository\ActivityTypeLOGRepository;
use App\Form\ActivityTypeType;
use App\Repository\ActivityTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Persistence\ManagerRegistry;


#[Route('/activityTypes')]
class ActivityTypeController extends AbstractController
{
    public $basePath = '';

    public function __construct(
        KernelInterface $appKernel,
        private ActivityTypeRepository $activityTypeRepository,
        private ActivityTypeLOGRepository $activityTypeLogRepository
    ) {
        $this->basePath = $appKernel->getProjectDir();
    }

    #[Route('/', name: 'app_activityTypes', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('activityType/index.html.twig', [
            'pageTitle' => 'Activity Types',
            'templatemtime' => str_replace('\\', '', get_class()) . filemtime($this->basePath . '/templates/activityType/index.html.twig'),
        ]);
    }

    #[Route('/', name: 'app_activityTypesPOST', methods: ['POST'])]
    public function indexPOST(Request $request, ManagerRegistry $doctrine): Response
    {
        $params = json_decode($request->query->get('params'), true);
        $sortingBy = isset($params['sort']) ? $params['sort'] : [];

        // NETWORK INTERFACE
        // CONSULTA NETWORK INTERFACE 3er grado
        $columns = array(
            'id' => 'at.id',
            'description' => 'at.description',
            'code' => 'at.code',
            'price' => 'at.price',
            'SAPname' => 'at.SAPname',
            'type' => 'at.type',
            'elementName' => 'ni.name',
            'environmentName' => 'e.name',
            'clientName' => 'c.name',
            'serviceName' => 's.name',
        );

        $joinTables = array(
            'ni' => array('ni'),
            'ns' => array('ni', 'ns'),
            'nd' => array('ni', 'ns', 'nd'),
            'e' => array('ni', 'ns', 'nd', 'e'),
            's' => array('ni', 'ns', 'nd', 'e', 's'),
            'c' => array('ni', 'ns', 'nd', 'e','s','c'),
        );

        // INICIALIZAR TABLE
        $table = array();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(ActivityType::class, 'at');
        
        if($request->get('active') === "false") {
            $query->where('at.active=FALSE');
        } else {
            $query->where('at.active=TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }
        
        // JOINS
        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'name' || key($column) == 'id') {
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
                                    case 'ni':
                                        $query->join('at.networkInterfaces', 'ni');
                                        break;
                                    case 'ns':
                                        $query->join('ni.networkVirtualSystem', 'ns');
                                        break;
                                    case 'nd':
                                        $query->join('ns.networkDevice', 'ns');
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
                                    case 'ni':
                                        $query->join('at.networkInterfaces', 'ni', \Doctrine\ORM\Query\Expr\Join::WITH, 'ni.active=TRUE');
                                        break;
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

        // Crear el $table en base al result
        foreach($query->getQuery()->getScalarResult() as $result) {
            $row = [];
            foreach($columnVisibility as $column) {
                if($column[key($column)] && isset($columns[key($column)])) {
                    switch(key($column)) {
                        default:
                            $row[key($column)] = $result[key($column)];
                    }
                }
            }
            $row['relationType'] = 'Network Interface';
            $table[] = $row;
        }

        // CONSULTA NETWORK INTERFACE 2do grado
        $columns = array(
            'id' => 'at.id',
            'description' => 'at.description',
            'code' => 'at.code',
            'price' => 'at.price',
            'SAPname' => 'at.SAPname',
            'type' => 'at.type',
            'elementName' => 'ni.name',
            'environmentName' => 'e.name',
            'clientName' => 'c.name',
            'serviceName' => 's.name',
        );

        $joinTables = array(
            'ni' => array('ni'),
            'ns' => array('ni', 'ns'),
            'e' => array('ns', 'e'),
            's' => array('ns', 'e', 's'),
            'c' => array('ns', 'e','s','c'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(ActivityType::class, 'at');
        
        if($request->get('active') === "false") {
            $query->where('at.active=FALSE');
        } else {
            $query->where('at.active=TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }
        
        // JOINS
        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'name' || key($column) == 'id') {
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
                                    case 'ni':
                                        $query->join('at.networkInterfaces', 'ni');
                                        break;
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
                                    case 'ni':
                                        $query->join('at.networkInterfaces', 'ni', \Doctrine\ORM\Query\Expr\Join::WITH, 'ni.active=TRUE');
                                        break;
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

        // Crear el $table en base al result
        foreach($query->getQuery()->getScalarResult() as $result) {
            $row = [];
            foreach($columnVisibility as $column) {
                if($column[key($column)] && isset($columns[key($column)])) {
                    switch(key($column)) {
                        default:
                            $row[key($column)] = $result[key($column)];
                    }
                }
            }
            $row['relationType'] = 'Network Interface';
            $table[] = $row;
        }

        // CONSULTA NETWORK INTERFACE 1er grado
        $columns = array(
            'id' => 'at.id',
            'description' => 'at.description',
            'code' => 'at.code',
            'price' => 'at.price',
            'SAPname' => 'at.SAPname',
            'type' => 'at.type',
            'elementName' => 'ni.name',
            'environmentName' => 'e.name',
            'clientName' => 'c.name',
            'serviceName' => 's.name',
        );

        $joinTables = array(
            'ni' => array('ni'),
            'e' => array('ni', 'e'),
            's' => array('ni', 'e', 's'),
            'c' => array('ni', 'e','s','c'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(ActivityType::class, 'at');
        
        if($request->get('active') === "false") {
            $query->where('at.active=FALSE');
        } else {
            $query->where('at.active=TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }
        
        // JOINS
        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'name' || key($column) == 'id') {
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
                                    case 'ni':
                                        $query->join('at.networkInterfaces', 'ni');
                                        break;
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
                                    case 'ni':
                                        $query->join('at.networkInterfaces', 'ni', \Doctrine\ORM\Query\Expr\Join::WITH, 'ni.active=TRUE');
                                        break;
                                    case 'e':
                                        $query->join('ni.environment', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'e.active=TRUE');
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

        // Crear el $table en base al result
        foreach($query->getQuery()->getScalarResult() as $result) {
            $row = [];
            foreach($columnVisibility as $column) {
                if($column[key($column)] && isset($columns[key($column)])) {
                    switch(key($column)) {
                        default:
                            $row[key($column)] = $result[key($column)];
                    }
                }
            }
            $row['relationType'] = 'Network Interface';
            $table[] = $row;
        }
        
        // NETWORK VIRTUAL SYSTEM
        // CONSULTA NETWORK VIRTUAL SYSTEM 1er grado
        $columns = array(
            'id' => 'at.id',
            'description' => 'at.description',
            'code' => 'at.code',
            'price' => 'at.price',
            'SAPname' => 'at.SAPname',
            'type' => 'at.type',
            'elementName' => 'ns.name',
            'environmentName' => 'e.name',
            'clientName' => 'c.name',
            'serviceName' => 's.name',
        );

        $joinTables = array(
            'ns' => array('ns'),
            'e' => array('ns', 'e'),
            's' => array('ns', 'e', 's'),
            'c' => array('ns', 'e','s','c'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(ActivityType::class, 'at');
        
        if($request->get('active') === "false") {
            $query->where('at.active=FALSE');
        } else {
            $query->where('at.active=TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }
        
        // JOINS
        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'name' || key($column) == 'id') {
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
                                        $query->join('at.networkVirtualSystems', 'ns');
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
                                        $query->join('at.networkVirtualSystems', 'ns', \Doctrine\ORM\Query\Expr\Join::WITH, 'ns.active=TRUE');
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

        // Crear el $table en base al result
        foreach($query->getQuery()->getScalarResult() as $result) {
            $row = [];
            foreach($columnVisibility as $column) {
                if($column[key($column)] && isset($columns[key($column)])) {
                    switch(key($column)) {
                        default:
                            $row[key($column)] = $result[key($column)];
                    }
                }
            }
            $row['relationType'] = 'Network Virtual System';
            $table[] = $row;
        }

        // CONSULTA NETWORK VIRTUAL SYSTEM 2do grado
        // Crear el $table en base al result
        $columns = array(
            'id' => 'at.id',
            'description' => 'at.description',
            'code' => 'at.code',
            'price' => 'at.price',
            'SAPname' => 'at.SAPname',
            'type' => 'at.type',
            'elementName' => 'ns.name',
            'environmentName' => 'e.name',
            'clientName' => 'c.name',
            'serviceName' => 's.name',
        );

        $joinTables = array(
            'ns' => array('ns'),
            'nd' => array('ns', 'nd'),
            'e' => array('nd', 'e'),
            's' => array('nd', 'e', 's'),
            'c' => array('nd', 'e','s','c'),
        );

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(ActivityType::class, 'at');
        
        if($request->get('active') === "false") {
            $query->where('at.active=FALSE');
        } else {
            $query->where('at.active=TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }
        
        // JOINS
        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'name' || key($column) == 'id') {
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
                                        $query->join('at.networkVirtualSystems', 'ns');
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
                                        $query->join('at.networkVirtualSystems', 'ns', \Doctrine\ORM\Query\Expr\Join::WITH, 'ns.active=TRUE');
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

        // Crear el $table en base al result
        foreach($query->getQuery()->getScalarResult() as $result) {
            $row = [];
            foreach($columnVisibility as $column) {
                if($column[key($column)] && isset($columns[key($column)])) {
                    switch(key($column)) {
                        default:
                            $row[key($column)] = $result[key($column)];
                    }
                }
            }
            $row['relationType'] = 'Network Virtual System';
            $table[] = $row;
        }

        // CONSULTA NETWORK DEVICE
        // Definicion de columnas + where basico Network Device
        $columns = array(
            'id' => 'at.id',
            'description' => 'at.description',
            'code' => 'at.code',
            'price' => 'at.price',
            'SAPname' => 'at.SAPname',
            'type' => 'at.type',
            'elementName' => 'nd.name',
            'environmentName' => 'e.name',
            'clientName' => 'c.name',
            'serviceName' => 's.name',
        );

        $joinTables = array(
            'nd' => array('nd'),
            'e' => array('nd', 'e'),
            's' => array('nd', 'e', 's'),
            'c' => array('nd', 'e', 's', 'c'),
        );
        
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $query = $em->createQueryBuilder();
        $query->from(ActivityType::class, 'at');
        
        if($request->get('active') === "false") {
            $query->where('at.active=FALSE');
        } else {
            $query->where('at.active=TRUE');
        }
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }

        // JOINS
        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'id') {
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
                                        $query->join('at.networkDevices', 'nd');
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
                                    case 'nd':
                                        $query->join('at.networkDevices', 'nd', \Doctrine\ORM\Query\Expr\Join::WITH, 'nd.active=TRUE');
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
        // JOINS
        // Crear el $table en base al result
        foreach($query->getQuery()->getScalarResult() as $result) {
            $row = [];
            foreach($columnVisibility as $column) {
                if($column[key($column)] && isset($columns[key($column)])) {
                    switch(key($column)) {
                        default:
                            $row[key($column)] = $result[key($column)];
                    }
                }
            }
            $row['relationType'] = 'Network Device';
            $table[] = $row;
        }

        // CONSULTA SIN RELACION
        $columns = array(
            'id' => 'at.id',
            'description' => 'at.description',
            'code' => 'at.code',
            'price' => 'at.price',
            'SAPname' => 'at.SAPname',
            'type' => 'at.type',
        );

        $joinTables = array(
            'ns' => array('ns'),
        );

        $em = $doctrine->getManager();
        
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $query = $em->createQueryBuilder();
        $query->from(ActivityType::class, 'at');
        
        if($request->get('active') === "false") {
            $query->where('at.active=FALSE');
        } else {
            $query->where('at.active=TRUE');
        }
        $query->andWhere('at.networkDevices IS EMPTY');
        $query->andWhere('at.networkVirtualSystems IS EMPTY');
        $query->andWhere('at.networkInterfaces IS EMPTY');
        
        foreach($sortingBy as $key => $sort) {
            $query->orderBy($columns[$sort['field']], $sort['dir']);
        }
        // Definicion de columnas + where basico

        // JOINS
        $joinedTables = array();
        $columnVisibility = json_decode($request->query->get('columnVisibility'), true);
        foreach($columnVisibility as $columnKey => $column) {
            if(key($column) == 'name' || key($column) == 'id') {
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
                                        $query->join('at.networkVirtualSystems', 'ns');
                                        break;
                                }
                            } else {
                                switch($tableToJoin) {
                                    case 'ns':
                                        $query->join('at.networkVirtualSystems', 'ns', \Doctrine\ORM\Query\Expr\Join::WITH, 'ns.active=TRUE');
                                        break;
                                }
                            }
                            $joinedTables[] = $tableToJoin;
                        }
                    }
                }
            }
        }
        // JOINS
        
        // Crear el $table en base al result
        foreach($query->getQuery()->getScalarResult() as $result) {
            $row = [];
            foreach($columnVisibility as $column) {
                if($column[key($column)] && isset($columns[key($column)])) {
                    switch(key($column)) {
                        default:
                            $row[key($column)] = $result[key($column)];
                    }
                }
            }
            $row['relationType'] = 'No relations';
            $table[] = $row;
        }

        return new Response(
            json_encode(array_values($table)),
            Response::HTTP_OK
        );
    }

    #[Route('/add', name: 'app_activityTypesAdd')]
    public function activityTypesAdd(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(ActivityTypeType::class, null, [
            'action' => $this->generateUrl('app_activityTypesAdd'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());

            if (count($errors) === 0) {
                $entityManager = $doctrine->getManager();
                $activityType = $form->getData();
                $entityManager->persist($activityType);
                $entityManager->flush();

                $this->addFlash('success', sprintf('New Activity Type added: %s', $activityType->getCode()));
                return $this->redirectToRoute('app_activityTypes');
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_activityTypes');
            }
        }

        return $this->render('activityType/_activityTypeForm.html.twig', [
            'form'  => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_activityTypesDetail')]
    public function activityTypesDetail(ActivityType $activityType): Response
    {
        return $this->render('_detail.html.twig', [
            'pageTitle' => 'Activity Type' . ' ' . $activityType->getCode(),
            'pageView' => $this->generateUrl('app_activityTypesView', ['id' => $activityType->getId()]),
            'auditView' => $this->generateUrl('app_activityTypesAudit', ['id' => $activityType->getId()]),
            'object'  => $activityType,
        ]);
    }

    #[Route('/{id}/view', name: 'app_activityTypesView')]
    public function activityTypesView(ActivityType $activityType): Response
    {
        return $this->render('activityType/_activityTypeView.html.twig', [
            'pageTitle' => $activityType->getCode(),
            'object'  => $activityType,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activityTypesEdit')]
    public function activityTypesEdit(ActivityType $activityType, Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(
            ActivityTypeType::class,
            $activityType,
            [
                'action' => $this->generateUrl('app_activityTypesEdit', ['id' => $activityType->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());

            if (count($errors) === 0) {
                $entityManager = $doctrine->getManager();
                $activityType = $form->getData();
                $entityManager->persist($activityType);
                $entityManager->flush();

                $this->addFlash('success', "Activity Type " . $activityType->getCode() . " updated");
                return $this->redirectToRoute('app_activityTypes');
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_activityTypes');
            }
        }

        return $this->render('activityType/_activityTypeForm.html.twig', [
            'form'  => $form->createView(),
            'object'  => $activityType,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_activityTypesDelete')]
    public function activityTypesDelete(ActivityType $activityType, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $entityManager->remove($activityType);
        $entityManager->flush();

        $this->addFlash('success', "Activity Type " . $activityType->getCode() . " removed");
        return $this->redirectToRoute('app_activityTypes');
    }

    #[Route('/{id}/audit', name: 'app_activityTypesAudit')]
    public function activityTypeAudit(ActivityType $at, Request $request): Response
    {
        $changes = array();
        $logs = $this->activityTypeLogRepository->findBy(array('idActivityType' => $at->getId()), array('DateLOG' => 'ASC'));

        foreach($logs as $key => $log) {
            if($key === 0) {
                $changes[] = array('field' => 'Description'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getDescription()
                                , 'after' => $log->getDescription()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'Code'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getCode()
                                , 'after' => $log->getCode()
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
                $changes[] = array('field' => 'Price'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getPrice()
                                , 'after' => $log->getPrice()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'SAPname'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getSAPname()
                                , 'after' => $log->getSAPname()
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
                $changes[] = array('field' => 'NetworkDevices'
                                , 'before' => ($key === 0) ? '' : implode(', ', array_map(fn($device) => $device->getName(), $logs[$key - 1]->getNetworkDevices()->toArray()))
                                , 'after' => implode(', ', array_map(fn($device) => $device->getName(), $log->getNetworkDevices()->toArray()))
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                
                $changes[] = array('field' => 'NetworkVirtualSystems'
                                , 'before' => ($key === 0) ? '' : implode(', ', array_map(fn($device) => $device->getName(), $logs[$key - 1]->getNetworkVirtualSystems()->toArray()))
                                , 'after' => implode(', ', array_map(fn($device) => $device->getName(), $log->getNetworkVirtualSystems()->toArray()))
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                $changes[] = array('field' => 'NetworkInterfaces'
                                , 'before' => ($key === 0) ? '' : implode(', ', array_map(fn($device) => $device->getName(), $logs[$key - 1]->getNetworkInterfaces()->toArray()))
                                , 'after' => implode(', ', array_map(fn($device) => $device->getName(), $log->getNetworkInterfaces()->toArray()))
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
            } else {
                if($log->getDescription() !== $logs[$key-1]->getDescription()) {
                    $changes[] = array('field' => 'Description'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getDescription()
                                    , 'after' => $log->getDescription()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getCode() !== $logs[$key-1]->getCode()) {
                    $changes[] = array('field' => 'Code'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getCode()
                                    , 'after' => $log->getCode()
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
                if($log->getPrice() !== $logs[$key-1]->getPrice()) {
                    $changes[] = array('field' => 'Price'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getPrice()
                                    , 'after' => $log->getPrice()
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getSAPname() !== $logs[$key-1]->getSAPname()) {
                    $changes[] = array('field' => 'SAPname'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getSAPname()
                                    , 'after' => $log->getSAPname()
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
                if($log->getNetworkDevices() !== $logs[$key-1]->getNetworkDevices()) {
                    $changes[] = array('field' => 'NetworkDevices'
                                    , 'before' => ($key === 0) ? '' : implode(', ', array_map(fn($device) => $device->getName(), $logs[$key - 1]->getNetworkDevices()->toArray()))
                                    , 'after' => implode(', ', array_map(fn($device) => $device->getName(), $log->getNetworkDevices()->toArray()))
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                
                if($log->getNetworkVirtualSystems() !== $logs[$key-1]->getNetworkVirtualSystems()) {
                    $changes[] = array('field' => 'NetworkVirtualSystems'
                                    , 'before' => ($key === 0) ? '' : implode(', ', array_map(fn($device) => $device->getName(), $logs[$key - 1]->getNetworkVirtualSystems()->toArray()))
                                    , 'after' => implode(', ', array_map(fn($device) => $device->getName(), $log->getNetworkVirtualSystems()->toArray()))
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                if($log->getNetworkInterfaces() !== $logs[$key-1]->getNetworkInterfaces()) {
                    $changes[] = array('field' => 'NetworkInterfaces'
                                    , 'before' => ($key === 0) ? '' : implode(', ', array_map(fn($device) => $device->getName(), $logs[$key - 1]->getNetworkInterfaces()->toArray()))
                                    , 'after' => implode(', ', array_map(fn($device) => $device->getName(), $log->getNetworkInterfaces()->toArray()))
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
        // dd($changesOrdered);

        if($request->get('format') == 'application/json') {
            return new JsonResponse($changesOrdered, Response::HTTP_OK);
        } else {
            return $this->render('_auditViewer.html.twig', [
                'changes'  => $changesOrdered,
            ]);
        }
    }
}
