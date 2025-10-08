<?php

namespace App\Controller;

use App\Entity\ApiApp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\ApiAppRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiAppController extends AbstractController
{
    public function __construct(private Security $security, private ApiAppRepository $apiAppRepository)
    {
    }

    #[Route('/apiapps/{id}', name: 'app_apiAppsDetail')]
    public function apiAppDetail(ApiApp $apiapp): Response
    {
        return new JsonResponse([
            'client_id' => $apiapp->getClientId(),
            'client_secret' => $apiapp->getClientSecret(),
            'access_token' => $apiapp->getAccessToken(),
            'expires_at' => $apiapp->getExpiresAt()->format('Y-m-d H:i:s'),
        ], Response::HTTP_OK);
    }

    #[Route('/apiapps/{id}/delete', name: 'app_apiAppsDelete')]
    public function apiAppDelete(ApiApp $apiapp, ManagerRegistry $doctrine): Response
    {
        if($this->isGranted('ROLE_ADMIN') || ($this->isGranted('ROLE_API_EDITOR') && $apiapp->getUser() == $this->security->getUser())) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($apiapp);
            $entityManager->flush();
            return new JsonResponse([], Response::HTTP_OK);
        } else {
            return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/oauth/v2/token', name: 'app_getToken', methods: ['POST'])]
    public function getToken(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $basicCredendials = $request->headers->get('authorization');
        $clientCredentials = explode(':', base64_decode(substr($basicCredendials, 6)));
        $apiApp = $this->apiAppRepository->findOneBy(array('clientId' => $clientCredentials[0], 'clientSecret' => $clientCredentials[1]));

        $apiApp->refreshToken();

        $entityManager->persist($apiApp);
        $entityManager->flush();

        if($apiApp) {
            return new JsonResponse([
                'token_type' => 'Bearer',
                'access_token' => $apiApp->getAccessToken(),
                'expires_in' => strtotime($apiApp->getExpiresAt()->format('Y-m-d H:i:s')) - time(),
            ], Response::HTTP_OK);
        } else {
            return new JsonResponse([
                'error' => 'invalid_client',
                'error_description' => 'Client Credentials Not Found',
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
