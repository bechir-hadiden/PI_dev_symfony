<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label'       => 'Username',
                'attr'        => [
                    'placeholder'  => 'e.g. hamdi123',
                    'autocomplete' => 'username',
                ],
                'help'        => 'Must start with a letter. Can contain letters and numbers.',
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr'  => ['placeholder' => 'Your full name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr'  => ['placeholder' => 'you@email.com'],
            ])
            ->add('phone', TextType::class, [
                'label'    => 'Phone (optional)',
                'required' => false,
                'attr'     => ['placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,  // we handle hashing manually in the controller
                'first_options'   => [
                    'label' => 'Password',
                    'attr'  => ['placeholder' => 'Min. 8 chars, upper/lower/number/symbol'],
                ],
                'second_options'  => [
                    'label' => 'Confirm Password',
                    'attr'  => ['placeholder' => 'Repeat password'],
                ],
                'invalid_message' => 'Passwords do not match.',
                'constraints'     => [
                    new Assert\NotBlank(['message' => 'Password is required.']),
                    new Assert\Length([
                        'min'        => 8,
                        'minMessage' => 'Password must be at least 8 characters.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        'message' => 'Password must contain uppercase, lowercase, a number, and a special character.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => User::class,
            'validation_groups' => ['Default', 'registration'],
        ]);
    }
}
