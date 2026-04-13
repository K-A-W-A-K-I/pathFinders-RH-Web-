<?php

namespace App\Form;

use App\Entity\CategorieFormation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CategorieFormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['data']->getIdCategorie() !== null;
        
        $builder
            ->add('nomCategorie', TextType::class, [
                'label'      => 'Nom de la catégorie',
                'empty_data' => '',
                'attr'       => ['placeholder' => 'Ex: Développement Web'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Le nom de la catégorie est obligatoire. Veuillez saisir un nom.'
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 150,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères. Vous avez saisi {{ value|length }} caractère(s).',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères. Vous avez saisi {{ value|length }} caractères.'
                    ),
                    new Assert\Regex(
                        pattern: '/^[\p{L}0-9\s\-\(\)\.\'&,]+$/u',
                        message: 'Le nom contient des caractères non autorisés. Seuls les lettres, chiffres, espaces, tirets, parenthèses, points, apostrophes, esperluettes et virgules sont acceptés.'
                    ),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => true,
                'attr'     => ['rows' => 4, 'placeholder' => 'Décrivez le domaine couvert par cette catégorie...'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'La description est obligatoire.'
                    ),
                    new Assert\Length(
                        max: 1000,
                        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('imageFile', FileType::class, [
                'label'    => 'Image de la catégorie',
                'mapped'   => false,
                'required' => !$isEdit,
                'attr'     => ['accept' => 'image/*'],
                'constraints' => $isEdit ? [] : [
                    new Assert\NotBlank(
                        message: 'L\'image est obligatoire.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CategorieFormation::class]);
    }
}