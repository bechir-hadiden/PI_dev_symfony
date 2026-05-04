<?php                    
                      
                      
  

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomClient', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['placeholder' => 'Votre nom', 'class' => 'form-control'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2])]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'votre@email.com', 'class' => 'form-control'],
                'constraints' => [new Assert\NotBlank(), new Assert\Email()]
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'choices' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5],
                'expanded' => true,
                'multiple' => false,
                'data' => 5,
                'attr' => ['class' => 'd-none']
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => [
                    'placeholder' => 'Ex: Paris, France',
                    'class' => 'form-control',
                    'id' => 'destination-input'
                ],
                'required' => false
            ])
            ->add('voyageId', IntegerType::class, [
                'label' => 'ID du voyage',
                'attr' => ['placeholder' => 'Ex: 1, 2, 3...', 'class' => 'form-control', 'min' => 1],
                'constraints' => [new Assert\NotBlank(), new Assert\Positive()]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => ['placeholder' => 'Partagez votre expérience...', 'rows' => 4, 'class' => 'form-control', 'id' => 'commentaire-input'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 5])]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Avis::class]);
    }
}