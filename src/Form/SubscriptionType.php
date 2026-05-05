<?php

namespace App\Form;

use App\Entity\Subscription;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Client (Utilisateur)',
                'attr' => ['class' => 'form-select']
            ])
            ->add('plan', ChoiceType::class, [
                'choices' => [
                    'Standard (Basic)' => 'basic',
                    'Elite (Premium)' => 'premium',
                ],
                'label' => 'Plan sélectionné',
                'attr' => ['class' => 'form-select']
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix du forfait (TND)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 150.00']
            ])
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'attr' => ['class' => 'form-control']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Actif' => Subscription::STATUS_ACTIVE,
                    'Suspendu' => Subscription::STATUS_SUSPENDED,
                    'Annulé' => Subscription::STATUS_CANCELLED,
                ],
                'label' => 'Statut initial',
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}
