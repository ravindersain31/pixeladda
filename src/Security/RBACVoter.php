<?php


namespace App\Security;

use App\Service\RBACManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RBACVoter extends Voter
{
    public function __construct(private readonly RBACManager $RBACManager)
    {
    }

    protected function supports($attribute, $subject): bool
    {
        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!is_array($attribute)) {
            $attribute = preg_replace('/^admin_/', '', $attribute);
        }
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }
        $roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles)) {
            return true;
        }

        if (is_array($attribute)) {
            foreach ($attribute as $attrib) {
                $attrib = preg_replace('/^admin_/', '', $attrib);
                if (in_array($attrib, $roles)) {
                    return true;
                }
            }
        }

        $permissions = $this->RBACManager->getPermissions($roles);

        // If attributes are array then check if any array item is allowed.
        if (is_array($attribute)) {
            foreach ($attribute as $attrib) {
                $attrib = preg_replace('/^admin_/', '', $attrib);
                if (in_array($attrib, $permissions)) {
                    return true;
                }
            }
        }

        return in_array($attribute, $permissions);

    }
}