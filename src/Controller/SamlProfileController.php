<?php

namespace App\Controller;

use App\Entity\SamlProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\SamlProfileRepository;
use App\Form\SamlProfileType;
#use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/samlProfiles')]
class SamlProfileController extends AbstractController
{
    /**
     * @var SamlProfileRepository
     */
    private $samlProfileRepository;
    
    public function __construct(SamlProfileRepository $samlProfileRepository)
    {
        $this->samlProfileRepository = $samlProfileRepository;
    }

    #[Route('/', name: 'app_samlProfiles')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $table = $this->samlProfileRepository->findAll();

        return $this->render('samlProfile/index.html.twig', [
            'pageTitle' => 'SAML Profiles',
            'table' => $table,
        ]);
    }
    
    #[Route('/add', name: 'app_samlProfilesAdd')]
    public function samlProfileAdd(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(SamlProfileType::class, null, [
            'action' => $this->generateUrl('app_samlProfilesAdd'),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());

            if(count($errors) === 0) {
                $entityManager = $doctrine->getManager();
                $samlProfile = $form->getData();
                $entityManager->persist($samlProfile);
                $entityManager->flush();
    
                $this->addFlash('success', sprintf('New OU added: %s', $samlProfile->getName() ));
                return $this->redirectToRoute('app_samlProfiles');
            } else {
                foreach($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_samlProfiles');
            }
        }

        return $this->render('samlProfile/samlProfilesForm.html.twig', [
            'form'  => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'app_samlProfilesDetail')]
    public function samlProfileDetail(SamlProfile $samlProfile, Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(SamlProfileType::class, $samlProfile, [
            'action' => $this->generateUrl('app_samlProfilesDetail', [ 'id' => $samlProfile->getId() ]),
            ]
        );
        $form->handleRequest($request);

        if($form->isSubmitted()) {
            $errors = $validator->validate($form->getData());

            if(count($errors) === 0) {
                $entityManager = $doctrine->getManager();
                $samlProfile = $form->getData();
                $entityManager->persist( $samlProfile );
                $entityManager->flush();
    
                $this->addFlash('success', "SAML Profile " . $samlProfile->getName() . " updated" );
                return $this->redirectToRoute('app_samlProfiles');
            } else {
                foreach($form->getErrors(true) as $error) {
                    $this->addFlash('error', (string) $error->getMessage());
                }
                return $this->redirectToRoute('app_samlProfiles');
            }
        }
        
        return $this->render('samlProfile/samlProfilesForm.html.twig', [
            'form'  => $form->createView(),
            'object'  => $samlProfile,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_samlProfilesDelete')]
    public function samlProfileDelete(SamlProfile $samlProfile, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $entityManager->remove($samlProfile);
        $entityManager->flush();

        $this->addFlash('success', "SAML Profile " . $samlProfile->getName() . " removed");
        return $this->redirectToRoute('app_samlProfiles');    
    }
}
