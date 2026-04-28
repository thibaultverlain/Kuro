<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description ne peut pas être vide.')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: "La date d'échéance ne peut pas être dans le passé."
    )]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $lastChangedAt = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[Assert\NotNull(message: 'La priorité est obligatoire.')]
    private ?Priority $priority = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[Assert\NotNull(message: 'Le statut est obligatoire.')]
    private ?Status $status = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'tasks')]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskHistory::class, cascade: ['persist', 'remove'])]
    private Collection $history;

    #[ORM\Column]
    private int $position = 0;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->history = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->lastChangedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; $this->touch(); return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; $this->touch(); return $this; }

    public function getDueDate(): ?\DateTimeImmutable { return $this->dueDate; }
    public function setDueDate(?\DateTimeImmutable $dueDate): static { $this->dueDate = $dueDate; $this->touch(); return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getLastChangedAt(): ?\DateTimeImmutable { return $this->lastChangedAt; }
    public function setLastChangedAt(\DateTimeImmutable $lastChangedAt): static { $this->lastChangedAt = $lastChangedAt; return $this; }

    public function getPriority(): ?Priority { return $this->priority; }
    public function setPriority(?Priority $priority): static { $this->priority = $priority; $this->touch(); return $this; }

    public function getStatus(): ?Status { return $this->status; }
    public function setStatus(?Status $status): static { $this->status = $status; $this->touch(); return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; $this->touch(); return $this; }

    public function getUsers(): Collection { return $this->users; }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addTask($this);
            $this->touch();
        }
        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeTask($this);
            $this->touch();
        }
        return $this;
    }

    public function getHistory(): Collection { return $this->history; }

    public function addHistory(TaskHistory $entry): static
    {
        if (!$this->history->contains($entry)) {
            $this->history->add($entry);
            $entry->setTask($this);
        }
        return $this;
    }

    public function removeHistory(TaskHistory $entry): static
    {
        if ($this->history->removeElement($entry)) {
            if ($entry->getTask() === $this) {
                $entry->setTask(null);
            }
        }
        return $this;
    }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function isLate(): bool
    {
        if (!$this->dueDate) {
            return false;
        }
        $isDone = $this->status && mb_strtolower($this->status->getName()) === 'terminée';
        return $this->dueDate < new \DateTimeImmutable() && !$isDone;
    }

    private function touch(): void
    {
        $this->lastChangedAt = new \DateTimeImmutable();
    }
}
