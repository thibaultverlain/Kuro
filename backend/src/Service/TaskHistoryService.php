<?php

namespace App\Service;

use App\Entity\TaskHistory;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TaskHistoryService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Enregistre une entrée dans l'historique d'une tâche.
     * Ne flush pas — le flush est délégué à l'appelant pour regrouper les requêtes.
     */
    public function log(Task $task, string $message, ?User $author = null): void
    {
        $entry = new TaskHistory();
        $entry->setTask($task);
        $entry->setChangelog($message);
        $entry->setAuthor($author);

        $this->em->persist($entry);
    }
}
