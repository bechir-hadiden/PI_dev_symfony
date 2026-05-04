<?php

namespace App\Form;

use App\Entity\TransportType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TransportTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du type',
                'attr'  => ['placeholder' => 'Ex: Bus, Taxi, Voiture…', 'maxlength' => 50],
            ])
            ->add('prixDepart', NumberType::class, [
                'label' => 'Prix de départ (DT)',
                'scale' => 2,
                'attr'  => ['placeholder' => '0.00', 'step' => '0.01'],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Image (optionnel)',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, WEBP).',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TransportType::class]);
    }
}
