<?php

namespace App\Form;

use App\Entity\ContenuModule;
use App\Entity\Formation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ContenuModuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('formation', EntityType::class, [
                'class'        => Formation::class,
                'choice_label' => 'titre',
                'label'        => 'Formation',
                'placeholder'  => '-- Choisir une formation --',
                'constraints'  => [
                    new Assert\NotNull(
                        message: 'Veuillez sélectionner une formation. Ce champ est obligatoire.'
                    ),
                ],
            ])
            ->add('nom', TextType::class, [
                'label'      => 'Nom du module',
                'empty_data' => '',
                'attr'       => ['placeholder' => 'Ex: Introduction à Symfony'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Le nom du module est obligatoire. Veuillez saisir un nom.'
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 200,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères. Vous avez saisi {{ value|length }} caractère(s).',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères. Vous avez saisi {{ value|length }} caractères.'
                    ),
                    new Assert\Regex(
                        pattern: '/^[\p{L}0-9\s\-\(\)\.\'&,:]+$/u',
                        message: 'Le nom contient des caractères non autorisés. Seuls les lettres, chiffres, espaces, tirets, parenthèses, points, apostrophes, esperluettes, virgules et deux-points sont acceptés.'
                    ),
                    new Assert\Regex(
                        pattern: '/^(?!\s).*(?<!\s)$/u',
                        message: 'Le nom ne peut pas commencer ou se terminer par un espace.'
                    ),
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label'    => 'Contenu',
                'required' => true,
                'attr'     => ['rows' => 6, 'placeholder' => 'Décrivez le contenu détaillé du module...'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Le contenu est obligatoire.'
                    ),
                    new Assert\Length(
                        max: 5000,
                        maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères. Vous avez saisi {{ value|length }} caractères.'
                    ),
                ],
            ])
            ->add('ordre', IntegerType::class, [
                'label'    => 'Ordre d\'affichage',
                'required' => true,
                'attr'     => ['placeholder' => '1', 'min' => 0],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'L\'ordre est obligatoire.'
                    ),
                    new Assert\PositiveOrZero(
                        message: 'L\'ordre doit être un nombre positif ou zéro (0, 1, 2, ...).'
                    ),
                    new Assert\LessThanOrEqual(
                        value: 9999,
                        message: 'L\'ordre ne peut pas dépasser {{ compared_value }}.'
                    ),
                    new Assert\Callback(function ($ordre, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $contenuModule = $form->getData();
                        $formation = $form->get('formation')->getData();
                        
                        if ($formation && $ordre !== null) {
                            // Vérifier si un autre module de la même formation a le même ordre
                            foreach ($formation->getContenuModules() as $module) {
                                if ($module->getOrdre() === $ordre && $module->getIdContenu() !== $contenuModule->getIdContenu()) {
                                    $context->buildViolation('Un module avec cet ordre existe déjà pour cette formation. Veuillez choisir un ordre différent.')
                                        ->addViolation();
                                    break;
                                }
                            }
                        }
                    }),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContenuModule::class]);
    }
}
