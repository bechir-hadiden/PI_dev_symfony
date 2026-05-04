<?php

namespace App\Form;

use App\Entity\Destination;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Regex;

class DestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la destination',
                'attr'  => [
                    'placeholder' => 'Ex: Toulouse, Dubai...',
                    'minlength'   => 2,
                    'maxlength'   => 100,
                ],
                'constraints' => [
                    new NotBlank(message: 'Le nom de la destination est obligatoire.'),
                    new Length(
                        min: 2, max: 100,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Regex(
                        pattern: '/^[\p{L}\s\-\'\,\.]+$/u',
                        message: 'Le nom ne peut contenir que des lettres, espaces et tirets.'
                    ),
                ],
            ])

            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr'  => [
                    'placeholder' => 'Ex: France, Tunisie...',
                    'minlength'   => 2,
                    'maxlength'   => 100,
                ],
                'constraints' => [
                    new NotBlank(message: 'Le pays est obligatoire.'),
                    new Length(
                        min: 2, max: 100,
                        minMessage: 'Le pays doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Regex(
                        pattern: '/^[\p{L}\s\-]+$/u',
                        message: 'Le pays ne peut contenir que des lettres et des tirets.'
                    ),
                ],
            ])

            ->add('codeIata', TextType::class, [
                'label'    => 'Code IATA (3 lettres)',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'TUN, CDG...',
                    'maxlength'   => 3,
                    'minlength'   => 3,
                    'style'       => 'text-transform:uppercase',
                ],
                'constraints' => [
                    new Length(
                        exactly: 3,
                        exactMessage: 'Le code IATA doit contenir exactement 3 lettres (ex: TUN, CDG).'
                    ),
                    new Regex(
                        pattern: '/^[A-Z]{3}$/',
                        message: 'Le code IATA doit contenir 3 lettres majuscules uniquement.'
                    ),
                ],
            ])

            ->add('imageFiles', FileType::class, [
                'label'    => '📷 Images (sélectionnez plusieurs)',
                'mapped'   => false,
                'required' => false,
                'multiple' => true,
                'attr'     => ['accept' => 'image/*'],
                'constraints' => [
                    new All([
                        'constraints' => [
                            new Image(
                                maxSize: '3M',
                                maxSizeMessage: 'Chaque image ne doit pas dépasser 3 Mo.',
                                mimeTypesMessage: 'Veuillez uploader un fichier image valide (jpg, png, webp).',
                                maxWidth: 5000,
                                maxHeight: 5000,
                                maxWidthMessage: 'L\'image est trop large (max {{ max_width }}px).',
                                maxHeightMessage: 'L\'image est trop haute (max {{ max_height }}px).',
                            ),
                        ],
                    ]),
                ],
            ])

            ->add('youtubeSearch', TextType::class, [
                'label'    => '🎬 Rechercher une vidéo YouTube',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Ex: Toulouse city tour, Dubai travel...',
                    'id'          => 'youtubeSearch',
                    'maxlength'   => 100,
                ],
            ])

            ->add('videoUrl', TextType::class, [
                'label'    => 'ID Vidéo YouTube sélectionnée',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'dQw4w9WgXcQ',
                    'id'          => 'videoUrlField',
                    'readonly'    => true,
                    'maxlength'   => 11,
                ],
                'constraints' => [
                    new Length(
                        exactly: 11,
                        exactMessage: 'L\'ID YouTube doit contenir exactement 11 caractères (ex: dQw4w9WgXcQ).'
                    ),
                    new Regex(
                        pattern: '/^[a-zA-Z0-9_\-]{11}$/',
                        message: 'L\'ID YouTube ne doit contenir que des lettres, chiffres, tirets et underscores.'
                    ),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'rows'        => 4,
                    'placeholder' => 'Décrivez la destination...',
                    'maxlength'   => 2000,
                ],
                'constraints' => [
                    new Length(
                        min: 10, max: 2000,
                        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('order', IntegerType::class, [
                'label'    => 'Ordre d\'affichage',
                'required' => false,
                'attr'     => [
                    'placeholder' => '1, 2, 3...',
                    'min'         => 0,
                ],
                'constraints' => [
                    new PositiveOrZero(message: 'L\'ordre doit être un nombre positif ou zéro.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Destination::class]);
    }
}