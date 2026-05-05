<?php

namespace App\Form;

use App\Entity\Hotel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class HotelFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Hotel Name',
                'attr'  => ['placeholder' => 'Grand Hotel Paris'],
            ])
            ->add('location', TextType::class, [
                'label' => 'Address / Location',
                'attr'  => ['placeholder' => 'Champs-Élysées, Paris'],
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'attr'  => ['placeholder' => 'Paris'],
            ])
            ->add('country', TextType::class, [
                'label' => 'Country',
                'attr'  => ['placeholder' => 'France'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['rows' => 5, 'placeholder' => 'Describe the hotel experience...'],
            ])
            ->add('pricePerNight', NumberType::class, [
                'label' => 'Price per Night (€)',
                'scale' => 2,
                'attr'  => ['placeholder' => '250.00'],
            ])
            ->add('pricePerWeek', NumberType::class, [
                'label'    => 'Price per Week (€)',
                'required' => false,
                'scale'    => 2,
                'attr'     => ['placeholder' => '1500.00'],
            ])
            ->add('checkInTime', TextType::class, [
                'label'    => 'Check-in Time',
                'required' => false,
                'attr'     => ['placeholder' => '15:00'],
            ])
            ->add('checkOutTime', TextType::class, [
                'label'    => 'Check-out Time',
                'required' => false,
                'attr'     => ['placeholder' => '11:00'],
            ])
            ->add('contactEmail', TextType::class, [
                'label'    => 'Contact Email',
                'required' => false,
                'attr'     => ['placeholder' => 'info@hotel.com'],
            ])
            ->add('contactPhone', TextType::class, [
                'label'    => 'Contact Phone',
                'required' => false,
                'attr'     => ['placeholder' => '+33 1 XX XX XX XX'],
            ])
            // Amenities as a free-text comma-separated field (easier UX)
            ->add('amenitiesInput', TextType::class, [
                'label'    => 'Amenities',
                'mapped'   => false,          // not a real property on Hotel entity
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Free WiFi, Pool, Spa, Restaurant',
                    'class'       => 'amenities-input',
                ],
                'help' => 'Separate amenities with commas.',
            ])
            // Multi-file upload
            ->add('photos', FileType::class, [
                'label'       => 'Hotel Photos',
                'mapped'      => false,        // handled in controller via HotelService
                'required'    => false,
                'multiple'    => true,
                'attr'        => ['accept' => 'image/*', 'multiple' => 'multiple'],
                'constraints' => [
                    new All([
                        'constraints' => [
                            new Image([
                                'maxSize'          => '5M',
                                'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                                'mimeTypesMessage' => 'Only JPEG, PNG and WebP images are allowed.',
                            ]),
                        ],
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hotel::class,
        ]);
    }
}
