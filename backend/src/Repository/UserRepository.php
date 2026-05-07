<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne les utilisateurs ayant le rôle donné.
     *
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT id FROM "user"
            WHERE roles::jsonb @> :role::jsonb
        ';

        $result = $conn->executeQuery($sql, [
            'role' => json_encode([$role]),
        ]);

        $ids = array_column($result->fetchAllAssociative(), 'id');

        if (!$ids) {
            return [];
        }

        return $this->findBy(['id' => $ids]);
    }

    /**
     * Retourne tous les membres des projets passés en paramètre (sans doublons).
     *
     * @param int[] $projectIds
     * @return User[]
     */
    public function findMembersOfProjects(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->innerJoin('u.projects', 'p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $projectIds)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
