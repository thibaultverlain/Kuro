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
     *
     * @return Task[]
     */
    public function search(
        User $currentUser,
        string $query = '',
        ?string $status = null,
        ?int $projectId = null,
        ?int $assignedUserId = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->distinct()
            ->innerJoin('t.project', 'p')
            ->innerJoin('p.users', 'u')
            ->leftJoin('t.status', 's')
            ->leftJoin('t.users', 'a')
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

        if ($assignedUserId !== null) {
            $qb->andWhere('a.id = :assignedUser')
               ->setParameter('assignedUser', $assignedUserId);
        }

        return $qb
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le nombre de tâches créées et terminées par jour sur les N derniers jours.
     * Utilisé pour le graphique d'activité du dashboard.
     *
     * @return array{labels: string[], created: int[], completed: int[]}
     */
    public function getActivityLastDays(User $user, int $days = 7): array
    {
        $labels    = [];
        $created   = [];
        $completed = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = new \DateTimeImmutable("-{$i} days");
            $labels[]  = $date->format('d/m');

            $dayStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 00:00:00');
            $dayEnd   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 23:59:59');

            // Tâches créées ce jour
            $createdCount = (int) $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->innerJoin('t.project', 'p')
                ->innerJoin('p.users', 'u')
                ->andWhere('u = :user')
                ->andWhere('t.createdAt BETWEEN :start AND :end')
                ->setParameter('user', $user)
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            // Tâches terminées ce jour (lastChangedAt + statut Terminée)
            $completedCount = (int) $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->innerJoin('t.project', 'p')
                ->innerJoin('p.users', 'u')
                ->innerJoin('t.status', 's')
                ->andWhere('u = :user')
                ->andWhere('s.name = :done')
                ->andWhere('t.lastChangedAt BETWEEN :start AND :end')
                ->setParameter('user', $user)
                ->setParameter('done', 'Terminée')
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $created[]   = $createdCount;
            $completed[] = $completedCount;
        }

        return [
            'labels'    => $labels,
            'created'   => $created,
            'completed' => $completed,
        ];
    }
}
