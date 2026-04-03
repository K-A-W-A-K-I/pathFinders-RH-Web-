<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextareaType::class, [
                'label' => 'Énoncé de la question',
                'attr'  => ['rows' => 3, 'placeholder' => 'Saisissez la question...'],
            ])
            ->add('choix1', TextType::class, [
                'label' => 'Choix A',
                'attr'  => ['placeholder' => 'Première réponse possible'],
            ])
            ->add('choix2', TextType::class, [
                'label' => 'Choix B',
                'attr'  => ['placeholder' => 'Deuxième réponse possible'],
            ])
            ->add('choix3', TextType::class, [
                'label'    => 'Choix C (optionnel)',
                'required' => false,
                'attr'     => ['placeholder' => 'Troisième réponse possible'],
            ])
            ->add('choix4', TextType::class, [
                'label'    => 'Choix D (optionnel)',
                'required' => false,
                'attr'     => ['placeholder' => 'Quatrième réponse possible'],
            ])
            ->add('bonneReponse', ChoiceType::class, [
                'label'       => 'Bonne réponse',
                'placeholder' => '-- Sélectionner --',
                'choices'     => [
                    'Choix A (1)' => 1,
                    'Choix B (2)' => 2,
                    'Choix C (3)' => 3,
                    'Choix D (4)' => 4,
                ],
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Points attribués',
                'attr'  => ['placeholder' => 'Ex: 2'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            // Désactive la validation HTML5 — tout passe par le serveur
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
