<?php
namespace App\Form;

use App\Entity\Prime;
use App\Entity\FichesPaiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrimeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('fichesPaiement', EntityType::class, [
    'class' => FichesPaiement::class,
    'choice_label' => function (FichesPaiement $fiche) {
        return $fiche->getEmployee()->getUtilisateur()->getNom()
            . ' '
            . $fiche->getEmployee()->getUtilisateur()->getPrenom()
            . ' — '
            . $fiche->getDatePaiement()->format('M Y');
    },
    'query_builder' => function (\App\Repository\FichesPaiementRepository $repo) {
        return $repo->createQueryBuilder('f')
            ->innerJoin('f.employee', 'e')
            ->innerJoin('e.utilisateur', 'u')
            ->orderBy('u.nom', 'ASC');
    },
    'label' => 'Fiche de paiement',
    'placeholder' => '-- Choisir --',
])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
            ])
            ->add('montant', NumberType::class, [
                'label' => 'Montant',
                'scale' => 2,
            ])
            ->add('type_prime', ChoiceType::class, [
                'label' => 'Type de prime',
                'choices' => [
                    'Performance'   => 'performance',
                    'Ancienneté'    => 'anciennete',
                    'Exceptionnelle'=> 'exceptionnelle',
                ],
            ])
           ->add('date_attribution', DateType::class, [
            'widget' => 'single_text',
            'label' => "Date d'attribution",
            'attr' => [
                'max' => (new \DateTime())->format('Y-m-d'), // ← blocks future dates in browser
             ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prime::class,
        ]);
    }
}