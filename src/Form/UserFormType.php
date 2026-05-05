<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr'  => ['placeholder' => 'hamdi123'],
                'help'  => 'Must start with a letter.',
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr'  => ['placeholder' => 'Hamdi Dridi'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['placeholder' => 'user@email.com'],
            ])
            ->add('phone', TextType::class, [
                'label'    => 'Phone',
                'required' => false,
                'attr'     => ['placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('role', ChoiceType::class, [
                'label'   => 'Role',
                'choices' => [
                    'Client' => 'CLIENT',
                    'Admin'  => 'ADMIN',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label'    => $isEdit ? 'New Password (leave blank to keep current)' : 'Password',
                'mapped'   => false,
                'required' => !$isEdit,
                'attr'     => ['placeholder' => $isEdit ? 'Leave blank to keep current' : 'Min. 8 chars'],
                'constraints' => $isEdit ? [] : [
                    new Assert\NotBlank(['message' => 'Password is required.']),
                    new Assert\Length(['min' => 8]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        'message' => 'Password must contain upper, lower, number and symbol.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
    }
}
