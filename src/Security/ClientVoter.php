<?php

namespace App\Security;

use App\Entity\Client;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class ClientVoter extends Voter
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

        // only vote on `Client` objects
        if (!$subject instanceof Client) {
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

        // you know $subject is a Client object, thanks to `supports()`
        /** @var Client $client */
        $client = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($client, $user);
            case self::EDIT:
                return $this->canEdit($client, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(Client $client, User $user): bool
    {
        if ($this->security->isGranted('ROLE_BUSINESSRULES_EDITOR')) {
            return true;
        } else {
            return false;
        }
    }

    private function canView(Client $client, User $user): bool
    {
        try {
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
