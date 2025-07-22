<?php

namespace App\Form;

use App\Entity\Season;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la saison',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Haute saison été, Basse saison hiver...'
                ]
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Date de début de la saison'
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Date de fin de la saison'
            ])
            ->add('multiplier', NumberType::class, [
                'label' => 'Multiplicateur de prix',
                'scale' => 2,
                'data' => 1.00, // Valeur par défaut
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0.01',
                    'placeholder' => '1.00'
                ],
                'help' => 'Multiplicateur appliqué aux prix de base (ex: 1.20 = +20%, 0.80 = -20%)'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Saison active',
                'required' => false,
                'data' => true, // Actif par défaut
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Les saisons inactives ne seront pas prises en compte dans le calcul des prix.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Season::class,
        ]);
    }
}