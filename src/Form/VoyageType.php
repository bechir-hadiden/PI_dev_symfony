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
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', TextType::class, [
                'label' => 'Nom de la destination',
                'attr'  => [
                    'placeholder' => 'Ex: Djerba, Paris, Dubai...',
                    'minlength'   => 2,
                    'maxlength'   => 255,
                ],
                'constraints' => [
                    new NotBlank(message: 'Le nom de la destination est obligatoire.'),
                    new Length(
                        min: 2, max: 255,
                        minMessage: 'La destination doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'La destination ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            // ✅ OBLIGATOIRE — sans destination liée, les images ne s'affichent pas
            ->add('destinationRel', EntityType::class, [
                'label'        => 'Destination liée (obligatoire pour les images)',
                'class'        => Destination::class,
                'choice_label' => fn(Destination $d) => $d->getNom() . ' — ' . $d->getPays(),
                'placeholder'  => '— Sélectionner une destination —',
                'required'     => true,
                'constraints'  => [
                    new NotNull(message: 'Veuillez sélectionner une destination liée.'),
                ],
            ])

            ->add('paysDepart', TextType::class, [
                'label'    => 'Pays de départ',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Ex: Tunisie, France...',
                    'maxlength'   => 100,
                ],
                'constraints' => [
                    new Length(
                        min: 2, max: 100,
                        minMessage: 'Le pays de départ doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le pays de départ ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Regex(
                        pattern: '/^[\p{L}\s\-\,]+$/u',
                        message: 'Le pays de départ ne peut contenir que des lettres, espaces et tirets.'
                    ),
                ],
            ])

            ->add('dateDebut', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'attr'   => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                ],
                'constraints' => [
                    new NotNull(message: 'La date de début est obligatoire.'),
                    new GreaterThanOrEqual(
                        value: 'today',
                        message: 'La date de début ne peut pas être dans le passé.'
                    ),
                ],
            ])

            ->add('dateFin', DateType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
                'attr'   => [
                    'min' => (new \DateTime('+1 day'))->format('Y-m-d'),
                ],
                'constraints' => [
                    // ✅ dateFin > dateDebut géré dans Voyage.php (Entity)
                    // via #[Assert\GreaterThan(propertyPath: 'dateDebut')]
                    new NotNull(message: 'La date de fin est obligatoire.'),
                ],
            ])

            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'scale' => 2,
                'attr'  => [
                    'placeholder' => '0.00',
                    'min'         => 1,
                    'max'         => 999999,
                    'step'        => '0.01',
                ],
                'constraints' => [
                    new NotNull(message: 'Le prix est obligatoire.'),
                    new Positive(message: 'Le prix doit être un nombre positif.'),
                    new LessThanOrEqual(
                        value: 999999,
                        message: 'Le prix ne peut pas dépasser {{ compared_value }} TND.'
                    ),
                ],
            ])

            ->add('imagePath', TextType::class, [
                'label'    => 'URL de l\'image (optionnel)',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                    'maxlength'   => 255,
                ],
                'help'        => 'Si vide, les images de la destination liée seront utilisées.',
                'constraints' => [
                    new Url(message: 'Veuillez entrer une URL valide (commençant par https://).'),
                    new Length(
                        max: 255,
                        maxMessage: 'L\'URL ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'rows'        => 5,
                    'placeholder' => 'Décrivez ce voyage...',
                    'maxlength'   => 2000,
                ],
                'constraints' => [
                    new Length(
                        min: 10, max: 2000,
                        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Voyage::class]);
    }
}