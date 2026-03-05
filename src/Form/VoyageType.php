<?php

namespace App\Form;

use App\Entity\Destination;
use App\Entity\Voyage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', TextType::class, [
                'label' => 'Nom de la destination',
                'attr'  => ['placeholder' => 'Ex: Djerba, Paris, Dubai...'],
            ])
            ->add('destinationRel', EntityType::class, [
                'label'        => 'Lier à une destination (optionnel)',
                'class'        => Destination::class,
                'choice_label' => fn(Destination $d) => $d->getNom() . ' — ' . $d->getPays(),
                'placeholder'  => '— Sélectionner —',
                'required'     => false,
            ])
            ->add('paysDepart', TextType::class, [
                'label'    => 'Pays de départ',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Tunisie, France...'],
            ])
            ->add('dateDebut', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('dateFin', DateType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'scale' => 2,
                'attr'  => ['placeholder' => '0.00'],
            ])
            ->add('imagePath', TextType::class, [
                'label'    => 'URL de l\'image',
                'required' => false,
                'attr'     => ['placeholder' => 'https://...'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['rows' => 5, 'placeholder' => 'Décrivez ce voyage...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Voyage::class]);
    }
}