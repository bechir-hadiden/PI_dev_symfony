<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomClient', TextType::class, [
                'label' => 'Votre nom complet',
                'attr'  => ['placeholder' => 'Nom Prénom', 'maxlength' => 255],
            ])
            ->add('emailClient', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['placeholder' => 'votre@email.com'],
            ])
            ->add('nombrePlaces', IntegerType::class, [
                'label' => 'Nombre de places',
                'data'  => 1,
                'attr'  => ['min' => 1, 'max' => 99],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Reservation::class]);
    }
}
