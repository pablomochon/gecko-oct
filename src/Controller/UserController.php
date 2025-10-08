<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#use App\Controller\UserPasswordHasherInterface;
use App\Entity\User;
use App\Form\ApiAppType;
use App\Form\UserType;
use App\Repository\ApiAppRepository;
use App\Repository\UserRepository;
use App\Repository\UserLOGRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface; 

#[Route('/users')]
class UserController extends AbstractController
{
    private $security;
    private $userRepository;
    private $userLogRepository;
    private $groupRepository;
    private $groupLogRepository;
    private $clientRepository;
    private $serviceRepository;
    private $apiAppRepository;
    
    public function __construct(KernelInterface $appKernel
                              , Security $security
                              , UserRepository $userRepository
                              , UserLOGRepository $userLogRepository
                              , ApiAppRepository $apiAppRepository
                                )
    {
        $this->basePath = $appKernel->getProjectDir();
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->userLogRepository = $userLogRepository;
        $this->apiAppRepository = $apiAppRepository;
    }

    #[Route('/', name: 'app_users')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $table = array();
        $query = $em
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select(array('u.id'
                         , 'u.username'
                         , 'u.name'
                         , 'u.email'
                         , 'u.roles'))
            ->where('u.active=TRUE');
        
        foreach($query->getQuery()->getResult() as $result) {
            if(isset($table[$result['id']])) { # Concatenation of usergroup because of multiple entries of id because of the left join
                //$table[$result['id']]['usergroup'] .= ', ' . $result['usergroup'];
            } else {
                $table[$result['id']] = array( # First occurrence
                    'id' => $result['id'],
                    'username' => $result['username'],
                    'name' => $result['name'],
                    'email' => $result['email'],
                    'roles' => join(', ', $result['roles'])
                );
            }
        }

        return $this->render('user/index.html.twig', [
            'pageTitle' => 'Users',
            'table' => preg_replace('/"([^"]+)"\s*:\s*/', '$1:', json_encode(array_values($table))),
        ]);
    }
    
    #[Route('/{id}', name: 'app_usersDetail')]
    public function usersDetail(User $user, Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        # If a not ROLE_ADMIN user tries to see details from a different user
        if($this->security->getUser() != $user && !$this->security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_usersDetail', [ 'id' => $this->security->getUser()->getId() ]); # Redirect to him/herself
        }

        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('app_usersDetail', [ 'id' => $user->getId() ]),
            ]
        );
        
        $actualUsername = $user->getUsername();
        $actualPassword = $user->getPassword();
        $actualRoles = $user->getRoles();
        
        $form->handleRequest($request);
        
        if($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());
            if(count($errors) === 0) {
                $user->setUsername($actualUsername);
    
                $plaintextPassword = $user->getPassword();
                if(strlen($plaintextPassword)) {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $user,
                        $plaintextPassword
                    );
                    $user->setPassword($hashedPassword);
                } else {
                    $user->setPassword($actualPassword);
                }
    
                foreach($user->getRolesFixed() as $roleFixed) {
                    $actualRoles[] = $roleFixed;
                }
                $user->setRoles($actualRoles);
    
                $entityManager = $doctrine->getManager();
                $entityManager->flush();
    
                $this->addFlash('success', "User " . $user->getName() . " updated" );
                return $this->redirectToRoute('app_usersDetail', [ 'id' => $user->getId() ]);
            } else {
                foreach($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_usersDetail', [ 'id' => $user->getId() ]);
            }
        }
        
        return $this->render('user/_usersDetail.html.twig', [
            'pageTitle' => 'User ' . $user->getName(),
            'form'  => $form->createView(),
        ]);
    }

    #[Route('/add', name: 'app_usersAdd')]
    public function usersAdd(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(UserType::class, null, [
            'action' => $this->generateUrl('app_usersAdd'),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());

            if(count($errors) === 0) {
                $entityManager = $doctrine->getManager();
                $user = $form->getData();
                $user->setActive(true);
                $entityManager->persist($user);
                $entityManager->flush();
    
                $this->addFlash('success', sprintf('New user added: %s', $user->getName()));
                return $this->redirectToRoute('app_users');
            } else {
                foreach($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_users');
            }
        }

        return $this->render('user/usersForm.html.twig', [
            'form'  => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_usersEdit')]
    public function usersEdit(User $user, Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('app_usersEdit', [ 'id' => $user->getId() ]),
            ]
        );
        
        $actualUsername = $user->getUsername();
        $actualPassword = $user->getPassword();
        $actualRoles = $user->getRoles();
        
        $form->handleRequest($request);
        
        if($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());
            
            if(count($errors) === 0) {
                $user->setUsername($actualUsername);
    
                $plaintextPassword = $user->getPassword();
                if(strlen($plaintextPassword)) {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $user,
                        $plaintextPassword
                    );
                    $user->setPassword($hashedPassword);
                } else {
                    $user->setPassword($actualPassword);
                }
    
                foreach($user->getRolesFixed() as $roleFixed) {
                    $actualRoles[] = $roleFixed;
                }
                $user->setRoles($actualRoles);
                
                $entityManager = $doctrine->getManager();
                $entityManager->flush();
    
                $this->addFlash('success', "User " . $user->getName() . " updated" );
                //return $this->redirectToRoute('app_usersDetail', [ 'id' => $user->getId() ]);
                return $this->redirectToRoute('app_users');
            } else {
                foreach($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                //return $this->redirectToRoute('app_usersDetail', [ 'id' => $user->getId() ]);
                return $this->redirectToRoute('app_users');
            }
        }
        
        return $this->render('user/_usersForm.html.twig', [
            'form'  => $form->createView(),
        ]);
    }

    #[Route('/{id}/apiapps', name: 'app_usersApiApps')]
    public function apiapps(User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $form = $this->createForm(ApiAppType::class, null, [
            'action' => $this->generateUrl('app_usersApiApps', [ 'id' => $user->getId() ]),
            ]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $apiapp = $form->getData();
            $apiapp->setUser($user);

            $entityManager->persist($apiapp);
            $entityManager->flush();

            $this->addFlash('success', sprintf('New API App added: %s', $apiapp->getName()));
            return $this->redirectToRoute('app_users', [ 'id' => $user->getId() ]);
        }
        
        $table = $this->apiAppRepository->findBy(array('user' => $user));

        return $this->render('apiapps/index.html.twig', [
            'table' => $table,
            'formApiApp'  => $form->createView(),
        ]);
    }

    #[Route('/{id}/audit', name: 'app_usersAudit')]
    public function usersAudit(User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        $changes = array();
        $logs = $this->userLogRepository->findBy(array('idUser' => $user->getId()), array('DateLOG' => 'ASC'));

        foreach($logs as $key => $log) {
            if($key === 0) {              
                $changes[] = array('field' => 'Roles'
                                , 'before' => ($key === 0) ? '' : join(', ', $logs[$key-1]->getRoles())
                                , 'after' => join(', ', $log->getRoles())
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                , 'UserLOGuser' => $log->getUserLOG()->getUsername());

                $changes[] = array('field' => 'Name'
                                , 'before' => ($key === 0) ? '' : $logs[$key-1]->getName()
                                , 'after' => $log->getName()
                                , 'action' => $log->getAction()
                                , 'DateLOG' => $log->getDateLOG()
                                , 'UserLOGname' => $log->getUserLOG()->getName()
                                , 'UserLOGuser' => $log->getUserLOG()->getUsername());
            } else {
                if($log->getRoles() !== $logs[$key-1]->getRoles()) {
                    $changes[] = array('field' => 'Roles'
                                    , 'before' => ($key === 0) ? '' : join(', ', $logs[$key-1]->getRoles())
                                    , 'after' => join(', ', $log->getRoles())
                                    , 'action' => $log->getAction()
                                    , 'DateLOG' => $log->getDateLOG()
                                    , 'UserLOGname' => $log->getUserLOG()->getName()
                                    , 'UserLOGuser' => $log->getUserLOG()->getUsername());
                }
                
                if($log->getName() !== $logs[$key-1]->getName()) {
                    $changes[] = array('field' => 'Name'
                                    , 'before' => ($key === 0) ? '' : $logs[$key-1]->getName()
                                    , 'after' => $log->getName()
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
