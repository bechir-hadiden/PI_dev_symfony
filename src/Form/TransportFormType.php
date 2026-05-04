<?php

namespace App\Form;

use App\Entity\Transport;
use App\Entity\TransportType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transportType', EntityType::class, [
                'class'        => TransportType::class,
                'choice_label' => 'nom',
                'label'        => 'Type de transport',
                'placeholder'  => '-- Sélectionner un type --',
            ])
            ->add('compagnie', TextType::class, [
                'label' => 'Compagnie',
                'attr'  => ['placeholder' => 'Ex: TRANSTU, Louage Sfax…', 'maxlength' => 255],
            ])
            ->add('numero', TextType::class, [
                'label' => 'Numéro de véhicule',
                'attr'  => ['placeholder' => 'Ex: TN-1234, BUS-001…', 'maxlength' => 100],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité (places)',
                'attr'  => ['placeholder' => '0', 'min' => 1],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix / place (DT)',
                'scale' => 2,
                'attr'  => ['placeholder' => '0.00', 'step' => '0.01'],
            ])
            ->add('imageUrl', TextType::class, [
                'label'    => 'URL image (optionnel)',
                'required' => false,
                'attr'     => ['placeholder' => 'https://…'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['rows' => 4, 'placeholder' => 'Description du véhicule…'],
            ])
            ->add('localisation', TextType::class, [
                'label'    => 'Ville / Position du véhicule',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Tunis, Sousse, Sfax…', 'id' => 'localisation-input'],
            ])
            ->add('latitude', NumberType::class, [
                'label'    => 'Latitude (auto)',
                'required' => false,
                'scale'    => 6,
                'attr'     => ['id' => 'lat-input', 'readonly' => true, 'placeholder' => 'Auto-rempli'],
            ])
            ->add('longitude', NumberType::class, [
                'label'    => 'Longitude (auto)',
                'required' => false,
                'scale'    => 6,
                'attr'     => ['id' => 'lon-input', 'readonly' => true, 'placeholder' => 'Auto-rempli'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Transport::class]);
    }
}
