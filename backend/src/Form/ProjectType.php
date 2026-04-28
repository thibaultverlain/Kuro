<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label'    => 'Nom du projet',
                'required' => true,
            ])
            ->add('description', null, [
                'label'    => 'Description',
                'required' => false,
            ])
            ->add('users', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn(User $user) => $user->getName() ?: $user->getEmail(),
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false,
                'label'        => 'Membres du projet',
                'choices'      => $options['available_users'],
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => Project::class,
            'available_users' => [],
        ]);

        $resolver->setAllowedTypes('available_users', ['array']);
    }
}
