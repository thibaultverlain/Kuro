<?php

namespace App\DataFixtures;

use App\Entity\Notification;
use App\Entity\Priority;
use App\Entity\Project;
use App\Entity\Role;
use App\Entity\Status;
use App\Entity\Task;
use App\Entity\TaskHistory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // --- PRIORITIES ---
        $priorities = [];
        foreach (['Basse', 'Moyenne', 'Haute', 'Critique'] as $name) {
            $p = new Priority();
            $p->setName($name);
            $manager->persist($p);
            $priorities[] = $p;
        }

        // --- STATUSES ---
        $statuses = [];
        foreach (['À faire', 'En cours', 'En révision', 'Terminée'] as $name) {
            $s = new Status();
            $s->setName($name);
            $manager->persist($s);
            $statuses[] = $s;
        }

        // --- USERS ---
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail());
            $user->setName($faker->name());
            $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $manager->persist($user);
            $users[] = $user;
        }

        // 1 admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setName('Admin');
        $admin->setPassword(password_hash('admin', PASSWORD_BCRYPT));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $manager->persist($admin);
        $users[] = $admin;

        // --- PROJECTS ---
        $projects = [];
        for ($i = 0; $i < 5; $i++) {
            $project = new Project();
            $project->setName($faker->catchPhrase());
            $project->setDescription($faker->paragraph());

            // Ajouter des membres
            $members = $faker->randomElements($users, rand(2, 5));
            foreach ($members as $member) {
                $project->addUser($member);
            }

            $manager->persist($project);
            $projects[] = $project;
        }

        // --- TASKS ---
        $tasks = [];
        $position = 0;
        for ($i = 0; $i < 30; $i++) {
            $task = new Task();
            $task->setTitle($faker->sentence(4));
            $task->setDescription($faker->paragraph());
            $task->setDueDate(
                \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('now', '+2 months'))
            );
            $task->setProject($faker->randomElement($projects));
            $task->setPriority($faker->randomElement($priorities));
            $task->setStatus($faker->randomElement($statuses));
            $task->setPosition($position++);

            // Assigner des users
            $assignees = $faker->randomElements($users, rand(1, 3));
            foreach ($assignees as $assignee) {
                $task->addUser($assignee);
            }

            $manager->persist($task);
            $tasks[] = $task;
        }

        // --- TASK HISTORY ---
        foreach ($tasks as $task) {
            for ($i = 0; $i < rand(1, 4); $i++) {
                $history = new TaskHistory();
                $history->setTask($task);
                $history->setAuthor($faker->randomElement($users));
                $history->setChangelog($faker->sentence());
                $manager->persist($history);
            }
        }

        // --- NOTIFICATIONS ---
        $notifTypes = ['task_assigned', 'status_changed', 'date_changed', 'title_changed', 'user_added', 'task_deleted'];
        for ($i = 0; $i < 30; $i++) {
            $notif = new Notification();
            $notif->setUser($faker->randomElement($users));
            $type = $faker->randomElement($notifTypes);
            $notif->setType($type);
            $notif->setMessage($faker->sentence());
            $notif->setIsRead($faker->boolean(30));
            $manager->persist($notif);
        }

        $manager->flush();
    }
}