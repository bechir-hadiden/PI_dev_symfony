<?php

namespace App\Form;

use App\Entity\Offre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
// --- AJOUT DES IMPORTS POUR LES EVENEMENTS ---
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'attr' => ['class' => 'form-control rounded-pill', 'placeholder' => 'Ex: Promo Été']
            ])
            // src/Form/OffreType.php

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true, // <-- Force HTML5 validation (le navigateur bloque)
                'attr' => [
                    'class' => 'form-control rounded-4',
                    'placeholder' => 'Décrivez l\'offre en quelques mots...',
                    'rows' => 3
                ]
            ])

            ->add('taux_remise', IntegerType::class, [
                'attr' => ['class' => 'form-control rounded-pill', 'placeholder' => 'Ex: 20']
            ])
            ->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control rounded-pill']
            ])
            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control rounded-pill']
            ])
            ->add('category', ChoiceType::class, [
                'choices'  => [
                    'Choisir un type...' => '',
                    'VOYAGE' => 'VOYAGE',
                    'HOTEL' => 'HOTEL',
                    'VOL' => 'VOL',
                    'TRANSPORT' => 'TRANSPORT',
                ],
                'attr' => ['class' => 'form-select rounded-pill']
            ])
            // On laisse la liste vide ici, elle sera remplie par le Listener lors du clic sur "Save"
            ->add('id_target', ChoiceType::class, [
                'label' => 'Élément concerné',
                'mapped' => false,
                'choices' => [], 
                'attr' => ['class' => 'form-select rounded-pill']
            ])
            ->add('is_local_support', CheckboxType::class, [
                'label' => 'Soutien Local 🤝',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('image_url', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control rounded-pill', 'placeholder' => 'nom_image.jpg']
            ])
        ;

        // --- LE CODE MAGIQUE POUR VALIDER LE CHOIX DYNAMIQUE ---
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data || !isset($data['id_target'])) {
                return;
            }

            // On injecte l'ID reçu dans les choix autorisés pour que Symfony ne bloque pas la validation
            $form->add('id_target', ChoiceType::class, [
                'choices' => [$data['id_target'] => $data['id_target']],
                'mapped' => false,
                'label' => 'Élément concerné',
                'attr' => ['class' => 'form-select rounded-pill']
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
        ]);
    }
}