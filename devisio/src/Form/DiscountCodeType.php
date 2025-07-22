<?php

namespace App\Form;

use App\Entity\DiscountCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscountCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code de réduction',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: SUMMER2024, WELCOME10...',
                    'style' => 'text-transform: uppercase;'
                ],
                'help' => 'Code que les clients utiliseront (sera automatiquement en majuscules)'
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom du code',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Promotion été 2024, Code de bienvenue...'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description interne du code de réduction'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de réduction',
                'choices' => DiscountCode::getTypeChoices(),
                'attr' => [
                    'class' => 'form-control discount-type-select'
                ],
                'placeholder' => 'Choisir le type'
            ])
            ->add('value', NumberType::class, [
                'label' => 'Valeur de la réduction',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0.01',
                    'placeholder' => '10.00'
                ],
                'help' => 'Pourcentage (ex: 10 pour 10%) ou montant en euros selon le type'
            ])
            ->add('minimumAmount', NumberType::class, [
                'label' => 'Montant minimum',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '50.00'
                ],
                'help' => 'Montant minimum du panier pour pouvoir utiliser ce code'
            ])
            ->add('maximumDiscount', NumberType::class, [
                'label' => 'Réduction maximale (€)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control maximum-discount-field',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '100.00'
                ],
                'help' => 'Montant maximum de réduction (utile pour les pourcentages)'
            ])
            ->add('validFrom', DateType::class, [
                'label' => 'Valide à partir du',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Laisser vide pour une validité immédiate'
            ])
            ->add('validUntil', DateType::class, [
                'label' => 'Valide jusqu\'au',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Laisser vide pour une validité illimitée'
            ])
            ->add('usageLimit', IntegerType::class, [
                'label' => 'Limite d\'utilisation',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1',
                    'placeholder' => '100'
                ],
                'help' => 'Nombre maximum d\'utilisations (laisser vide pour illimité)'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Code actif',
                'required' => false,
                'data' => true, // Actif par défaut
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Les codes inactifs ne peuvent pas être utilisés'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DiscountCode::class,
        ]);
    }
}