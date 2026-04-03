<?php

namespace App\Form;

use App\Entity\CategorieFormation;
use App\Entity\Formation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la formation',
                'attr'  => ['placeholder' => 'Ex: Développement Web avec Symfony'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le titre est obligatoire.'),
                    new Assert\Length(
                        min: 3, max: 200,
                        minMessage: 'Le titre doit contenir au moins 3 caractères.',
                        maxMessage: 'Le titre ne peut pas dépasser 200 caractères.'
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => true,
                'attr'     => ['rows' => 4, 'placeholder' => 'Décrivez le contenu et les objectifs de la formation...'],
                'constraints' => [
                    new Assert\NotBlank(message: 'La description est obligatoire.'),
                    new Assert\Length(max: 2000, maxMessage: 'La description ne peut pas dépasser 2000 caractères.'),
                ],
            ])
            ->add('categorie', EntityType::class, [
                'class'        => CategorieFormation::class,
                'choice_label' => 'nomCategorie',
                'label'        => 'Catégorie',
                'placeholder'  => '-- Sélectionner une catégorie --',
                'constraints'  => [
                    new Assert\NotNull(message: 'Veuillez sélectionner une catégorie.'),
                ],
            ])
            ->add('formateur', TextType::class, [
                'label'    => 'Nom du formateur',
                'required' => true,
                'attr'     => ['placeholder' => 'Prénom Nom'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom du formateur est obligatoire.'),
                    new Assert\Length(max: 150, maxMessage: 'Le nom du formateur ne peut pas dépasser 150 caractères.'),
                ],
            ])
            ->add('dureeHeures', NumberType::class, [
                'label'    => 'Durée (heures)',
                'required' => true,
                'scale'    => 1,
                'attr'     => ['placeholder' => 'Ex: 20.5', 'min' => 0.5, 'step' => 0.5],
                'constraints' => [
                    new Assert\NotBlank(message: 'La durée est obligatoire.'),
                    new Assert\Positive(message: 'La durée doit être un nombre positif.'),
                    new Assert\LessThanOrEqual(value: 9999.9, message: 'La durée ne peut pas dépasser 9999.9 heures.'),
                ],
            ])
            ->add('capaciteMax', IntegerType::class, [
                'label' => 'Capacité maximale',
                'attr'  => ['placeholder' => 'Ex: 30', 'min' => 1],
                'constraints' => [
                    new Assert\NotBlank(message: 'La capacité maximale est obligatoire.'),
                    new Assert\Positive(message: 'La capacité maximale doit être un entier positif.'),
                    new Assert\LessThanOrEqual(value: 10000, message: 'La capacité ne peut pas dépasser 10 000.'),
                ],
            ])
            ->add('placeDisponible', IntegerType::class, [
                'label' => 'Places disponibles',
                'attr'  => ['placeholder' => 'Ex: 30', 'min' => 0],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nombre de places disponibles est obligatoire.'),
                    new Assert\PositiveOrZero(message: 'Les places disponibles ne peuvent pas être négatives.'),
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label'    => 'Date de début',
                'required' => true,
                'widget'   => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de début est obligatoire.'),
                ],
            ])
            ->add('dateFin', DateType::class, [
                'label'    => 'Date de fin',
                'required' => true,
                'widget'   => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de fin est obligatoire.'),
                ],
            ]);

        // Validation croisée : date_fin > date_debut ET place_disponible <= capacite_max
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var Formation $data */
            $data = $event->getData();

            // Règle : date_fin doit être supérieure à date_debut
            $dateDebut = $data->getDateDebut();
            $dateFin   = $data->getDateFin();
            if ($dateDebut && $dateFin && $dateFin <= $dateDebut) {
                $form->get('dateFin')->addError(
                    new FormError('La date de fin doit être strictement supérieure à la date de début.')
                );
            }

            // Règle : place_disponible <= capacite_max
            $capacite = $data->getCapaciteMax();
            $places   = $data->getPlaceDisponible();
            if ($capacite !== null && $places !== null && $places > $capacite) {
                $form->get('placeDisponible')->addError(
                    new FormError('Les places disponibles (' . $places . ') ne peuvent pas dépasser la capacité maximale (' . $capacite . ').')
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Formation::class]);
    }
}
