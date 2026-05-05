<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Retourne uniquement les tâches des projets dont l'utilisateur est membre.
     *
     * @return Task[]
     */
    public function findForUserProjects(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->innerJoin('p.users', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche et filtre les tâches accessibles à l'utilisateur.
     * Utilise un double JOIN pour filtrer par membre du projet.
     *
     * @return Task[]
     */
    public function search(
        User $currentUser,
        string $query = '',
        ?string $status = null,
        ?int $projectId = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->distinct()
            ->innerJoin('t.project', 'p')
            ->innerJoin('p.users', 'u')
            ->leftJoin('t.status', 's')
            ->andWhere('u = :currentUser')
            ->setParameter('currentUser', $currentUser);

        if ($query !== '') {
            $qb->andWhere('t.title LIKE :q OR t.description LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        if ($status !== null) {
            $qb->andWhere('s.name = :status')
               ->setParameter('status', $status);
        }

        if ($projectId !== null) {
            $qb->andWhere('p.id = :projectId')
               ->setParameter('projectId', $projectId);
        }

        return $qb
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
