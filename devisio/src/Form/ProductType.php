<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit/service',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Vol Paris-Londres, Hôtel 4*, Guide touristique...'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description détaillée du produit ou service'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => Product::getTypeChoices(),
                'attr' => [
                    'class' => 'form-control'
                ],
                'placeholder' => 'Choisir un type'
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix unitaire (€)',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Unité',
                'choices' => [
                    'Forfait' => 'forfait',
                    'Personne' => 'personne',
                    'Nuit' => 'nuit',
                    'Jour' => 'jour',
                    'Heure' => 'heure',
                    'Kilomètre' => 'km',
                    'Pièce' => 'pièce',
                    'Groupe' => 'groupe',
                    'Vol' => 'vol',
                    'Trajet' => 'trajet',
                    'Semaine' => 'semaine',
                    'Mois' => 'mois',
                    'Année' => 'année',
                    'Autre' => 'autre'
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'placeholder' => 'Choisir une unité'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Produit actif',
                'required' => false,
                'data' => true,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Les produits inactifs n\'apparaîtront pas dans les listes de sélection lors de la création de devis.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}