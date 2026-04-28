<?php

namespace App\Security;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Centralise la logique d'accès aux projets.
 *
 * Usage dans un contrôleur :
 *   $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);
 *   $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $project);
 */
class ProjectVoter extends Voter
{
    public const VIEW   = 'project_view';
    public const EDIT   = 'project_edit';
    public const DELETE = 'project_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        // Les admins ont accès à tout
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Pour les autres attributs, l'utilisateur doit être membre du projet
        return $project->getUsers()->contains($user);
    }
}
