<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire.'),
                    new Length(min: 2, minMessage: 'Le prénom doit contenir au moins 2 caractères.'),
                ],
                'attr' => ['placeholder' => 'Votre prénom'],
            ])
            ->add('nom', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(min: 2, minMessage: 'Le nom doit contenir au moins 2 caractères.'),
                ],
                'attr' => ['placeholder' => 'Votre nom'],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire.'),
                    new Email(message: 'Veuillez entrer un email valide.'),
                ],
                'attr' => ['placeholder' => 'votre@email.com'],
            ])
            ->add('telephone', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('destinationSouhaitee', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Paris, Bali...'],
            ])
            ->add('budget', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 2 000 TND'],
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [
                    new NotBlank(message: 'Le message est obligatoire.'),
                    new Length(min: 10, minMessage: 'Le message doit contenir au moins 10 caractères.'),
                ],
                'attr' => [
                    'placeholder' => 'Décrivez votre projet de voyage...',
                    'rows' => 4,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}