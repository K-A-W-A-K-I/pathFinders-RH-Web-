<?php

namespace App\Form;

use App\Entity\Offre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du poste',
                'attr'  => ['placeholder' => 'Ex: Développeur Full Stack', 'autocomplete' => 'off'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['rows' => 5, 'placeholder' => 'Décrivez le poste, les missions...'],
            ])
            ->add('domaine', ChoiceType::class, [
                'label'       => 'Domaine',
                'placeholder' => '-- Choisir un domaine --',
                'choices'     => [
                    'IT'         => 'IT',
                    'Finance'    => 'Finance',
                    'Marketing'  => 'Marketing',
                    'RH'         => 'RH',
                    'Vente'      => 'Vente',
                    'Production' => 'Production',
                ],
            ])
            ->add('typeContrat', ChoiceType::class, [
                'label'       => 'Type de contrat',
                'placeholder' => '-- Choisir un contrat --',
                'choices'     => [
                    'CDI'       => 'CDI',
                    'CDD'       => 'CDD',
                    'Stage'     => 'Stage',
                    'Freelance' => 'Freelance',
                ],
            ])
            ->add('salaireMin', IntegerType::class, [
                'label' => 'Salaire minimum (DT)',
                'attr'  => ['placeholder' => 'Ex: 800'],
            ])
            ->add('salaireMax', IntegerType::class, [
                'label' => 'Salaire maximum (DT)',
                'attr'  => ['placeholder' => 'Ex: 2000'],
            ])
            ->add('scoreMinimum', IntegerType::class, [
                'label' => 'Score minimum requis (%)',
                'attr'  => ['placeholder' => 'Entre 0 et 100'],
            ])
            ->add('dureeTestMinutes', IntegerType::class, [
                'label' => 'Durée du test (minutes)',
                'attr'  => ['placeholder' => 'Entre 5 et 180'],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Active'   => 'active',
                    'Inactive' => 'inactive',
                    'Clôturée' => 'cloturee',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
            // Désactive la validation HTML5 — tout passe par le serveur
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
