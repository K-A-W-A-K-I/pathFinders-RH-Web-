<?php
namespace App\Form;

use App\Entity\FichesPaiement;
use App\Entity\Employee;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FichesPaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('employee', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => function (Employee $employee) {
                    return $employee->getUtilisateur()->getNom() . ' ' . $employee->getUtilisateur()->getPrenom();
                },
                'label' => 'Employé',
                'placeholder' => '-- Choisir --',
            ])
            ->add('date_paiement', DateType::class, [
                'widget' => 'single_text', 
                'label' => 'Date de paiement',
            ])
            ->add('type_paiement', ChoiceType::class, [
                'label' => 'Type de paiement',
                'choices' => [
                    'Virement' => 'virement',
                    'Chèque'   => 'cheque',
                    'Espèces'  => 'especes',
                ],
            ])

                // montant_deduction is auto-calculated, so we don't include it in the form
                // montant_taxe is auto-calculated, so we don't include it in the form  
        ;
        // NOTE: montant_deduction is NOT here because it's auto-calculated
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FichesPaiement::class,
        ]);
    }
}