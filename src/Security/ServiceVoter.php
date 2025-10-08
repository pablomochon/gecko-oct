<?php

namespace App\Security;

use App\Entity\Service;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class ServiceVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        // only vote on `Service` objects
        if (!$subject instanceof Service) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Service object, thanks to `supports()`
        /** @var Service $service */
        $service = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($service, $user);
            case self::EDIT:
                return $this->canEdit($service, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(Service $service, User $user): bool
    {
        if($this->security->isGranted('ROLE_BUSINESSRULES_EDITOR')) {
            return true;
        } else {
            return false;
        }
    }

    private function canView(Service $service, User $user): bool
    {
        try {
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}