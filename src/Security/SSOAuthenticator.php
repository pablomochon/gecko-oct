<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\SamlProfileRepository;
use App\Repository\UserRepository;
use App\Services\ssoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SSOAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public function __construct(
        private UserRepository $userRepository,
        private SamlProfileRepository $samlProfileRepository,
        private RouterInterface $router,
        private ssoService $ssoService,
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface $entityManager
    ){}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/login'
            && $request->isMethod('POST')
            && $request->request->get('SAMLResponse');
    }

    public function authenticate(Request $request): Passport
    {
        $this->ssoService->processResponse();
        $errors = $this->ssoService->getErrors();
        if(!empty($errors)) {
            throw new CustomUserMessageAuthenticationException(
                $this->ssoService->getLastErrorReason()
            );
        }

        $attributes = $this->ssoService->getAttributes();
        //dump('atr ==>',$attributes);
        $username = $attributes['uid'][0];
        
        $passport = new SelfValidatingPassport(
            new UserBadge($username, function ($userIdentifier) use ($attributes) {
                $user = $this->userRepository->findOneBy([
                    'username' => $userIdentifier
                ]);

                $name     = ucwords(mb_strtolower($attributes['givenName'][0] . " ". $attributes['sn'][0]));
                $email    = strtolower($attributes['mail'][0]);
                
                if( $user && $user->isActive() == false ) {
                    throw new UserNotFoundException();
                }

                if( !$user ) {
                    $user = $this->userRepository->findOneBy([
                        'email' => strtolower($attributes['mail'][0])
                    ]);
                    if($user) {
                        throw new CustomUserMessageAuthenticationException('Username/Email mismatch.');
                    }

                    $user = $this->createUser( $attributes );
                }
                
                if( $user && ($user->getName() != $name || $user->getEmail() != $email)) {
                    $user->setName($name);
                    $user->setEmail($email);
                }
                $user->setRoles(array());
                
                $samlProfile = $this->samlProfileRepository->findOneBy(array('name' => $attributes['ou'][0]));
                if($samlProfile) {
                    $user->setRoles($samlProfile->getRoles());
                }

                if(in_array($attributes['ou'][0],  $this->ssoService->_settings["groups_role"]["ROLE_ADMIN"]) && !in_array('ROLE_ADMIN', $user->getRoles())) {
                    $user->setRoles(array_merge($user->getRoles(), array('ROLE_ADMIN')));
                }

                if($user->getRolesFixed()) {
                    foreach($user->getRolesFixed() as $role) {
                        if(!in_array($role, $user->getRoles())) {
                            $user->setRoles(array_merge(array($role), $user->getRoles()));
                        }
                    }
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
        $passport->setAttribute('ou', $attributes['ou'][0]);

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        /*if (null !== $user->getLocale()) {
            $request->getSession()->set('_locale', $user->getLocale());
        }*/
        if( $target = $this->getTargetPath( $request->getSession(), $firewallName )) {
            return new RedirectResponse( $target );
        }
        return new RedirectResponse( $this->router->generate('app_dashboard') );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set( Security::AUTHENTICATION_ERROR, $exception );
        $request->getSession()->set( Security::LAST_USERNAME, $request->request->get('_username') );
        return new RedirectResponse( $this->router->generate('app_login') );
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_login')
        );
    }

    private function createUser(array $samlAttributes)
    {
        $username = $samlAttributes['uid'][0];
        $name     = ucwords( mb_strtolower( $samlAttributes['givenName'][0] . " ". $samlAttributes['sn'][0] ) );
        $email    = strtolower(  $samlAttributes['mail'][0] );
        $password = 'Primera1!';
        
        $user = new User();
        $user->setName( $name )
            ->setUsername( $username )
            ->setEmail( $email )
            ->setPassword( $this->passwordEncoder->hashPassword( $user, $password ) )
            ->setActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }
}
