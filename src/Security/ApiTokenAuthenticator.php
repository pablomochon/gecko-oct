<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ApiAppRepository;

class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private $apiAppRepository;

    public function __construct(ApiAppRepository $apiAppRepository)
    {
        $this->apiAppRepository = $apiAppRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('authorization') 
            && !$request->headers->has('php-auth-user') 
            && $request->getPathInfo() !== '/oauth/v2/token';
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('authorization');
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $apiApp = $this->apiAppRepository->findOneBy(array('accessToken' => substr($apiToken, 7)));
        if (null === $apiApp || $apiApp->getExpiresAt() < date('Y-m-d H:i:s')) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }
        $user = $apiApp->getUser();
        
        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // allow the authentication to continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessageKey()
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'message' => 'No Api Token provided.'
        ], Response::HTTP_UNAUTHORIZED);
    }
}
