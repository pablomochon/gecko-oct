<?php

namespace App\EventSubscriber;

use App\Entity\UserLoginLOG;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use App\Repository\UserRepository;

/**
 * Stores the locale of the user in the session after the
 * login. This can be used by the LocaleSubscriber afterwards.
 */
class UserLoginSubscriber implements EventSubscriberInterface
{
    private $requestStack;
    private $entityManager;
    private $userRepository;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        $user = $event->getUser();

        /* if (null !== $user->getLocale()) {
            $this->requestStack->getSession()->set('_locale', $user->getLocale());
        } */
        $user = $event->getUser();
        if(get_class($event->getAuthenticator()) != 'App\Security\ApiTokenAuthenticator') {
            $loginLOG = new UserLoginLOG();
            $loginLOG->setUser($user);
            $loginLOG->setSuccess(true);
            $loginLOG->setFirewall($event->getFirewallName());
            $loginLOG->setAuthenticator(get_class($event->getAuthenticator()));

            if($event->getPassport()->getAttribute('ou')) {
                $loginLOG->setMessage('SAML Profile ' . $event->getPassport()->getAttribute('ou'));
            }

            $this->entityManager->persist($loginLOG);
            $this->entityManager->flush();
        }
    }

    public function onLoginFailure(LoginFailureEvent $event)
    {
        $loginLOG = new UserLoginLOG();
        
        $loginLOG->setSuccess(false);
        $loginLOG->setFirewall($event->getFirewallName());
        $loginLOG->setAuthenticator(get_class($event->getAuthenticator()));

        $user = $this->userRepository->findOneBy([
            'username' => $event->getRequest()->get('_username')
        ]);

        if($user) {
            $loginLOG->setUser($user);
            $loginLOG->setMessage($event->getException()->getMessage());
        } else {
            $loginLOG->setMessage('Username: ' . $event->getRequest()->get('_username') . '. ' . $event->getException()->getMessage());
        }
        $this->entityManager->persist($loginLOG);
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }
}