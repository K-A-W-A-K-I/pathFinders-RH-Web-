<?php

namespace App\Form;

use App\Entity\CategorieEvenement;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Titre de l evenement',
                    'class' => 'form-control',
                    'maxlength' => 100,
                    'required' => true,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description de l evenement...',
                    'rows' => 4,
                    'class' => 'form-control',
                    'maxlength' => 2000,
                ],
            ])
            ->add('categorie', EntityType::class, [
                'label' => 'Categorie',
                'class' => CategorieEvenement::class,
                'choice_label' => 'nomCategorie',
                'placeholder' => 'Choisir une categorie',
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('typeEvenement', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Professionnel' => 'Professionnel',
                    'Non professionnel' => 'Non professionnel',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'Actif',
                    'Complet' => 'Complet',
                    'Annule' => 'Annulé',
                    'Termine' => 'Terminé',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de debut',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Lieu de l evenement',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
            ])
            ->add('capaciteMax', NumberType::class, [
                'label' => 'Capacite maximale',
                'attr' => [
                    'placeholder' => '100',
                    'class' => 'form-control',
                    'min' => 1,
                    'step' => 1,
                    'required' => true,
                ],
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix (TND)',
                'currency' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => 0,
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image de l evenement',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '4M',
                        'mimeTypesMessage' => 'Veuillez choisir une image valide (JPG, PNG, WEBP ou GIF).',
                        'maxSizeMessage' => 'L image ne doit pas depasser 4 Mo.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/png,image/jpeg,image/webp,image/gif',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'constraints' => [
                new Callback([$this, 'validateDates']),
            ],
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }

    public function validateDates(Evenement $evenement, ExecutionContextInterface $context): void
    {
        $today = new \DateTimeImmutable('today');

        if ($evenement->getDateDebut() !== null) {
            $dateDebut = \DateTimeImmutable::createFromInterface($evenement->getDateDebut());
            if ($dateDebut < $today) {
                $context->buildViolation('La date de debut doit etre aujourd hui ou dans le futur.')
                    ->atPath('dateDebut')
                    ->addViolation();
            }
        }

        if ($evenement->getDateDebut() !== null && $evenement->getDateFin() !== null) {
            $dateDebut = \DateTimeImmutable::createFromInterface($evenement->getDateDebut());
            $dateFin = \DateTimeImmutable::createFromInterface($evenement->getDateFin());

            if ($dateFin < $dateDebut) {
                $context->buildViolation('La date de fin doit etre apres ou egale a la date de debut.')
                    ->atPath('dateFin')
                    ->addViolation();
            }
        }

        if ($evenement->getCapaciteMax() !== null && $evenement->getCapaciteMax() < $evenement->getPlacesReservees()) {
            $context->buildViolation('La capacite maximale doit etre superieure ou egale aux places reservees.')
                ->atPath('capaciteMax')
                ->addViolation();
        }
    }
}
