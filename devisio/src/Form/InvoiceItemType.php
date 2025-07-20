<?php

namespace App\Form;

use App\Entity\InvoiceItem;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class InvoiceItemType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $company = $this->security->getUser()->getCompany();

        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'label' => 'Produit',
                'placeholder' => 'Sélectionner un produit',
                'required' => false,
                'query_builder' => function (ProductRepository $er) use ($company) {
                    return $er->createQueryBuilder('p')
                        ->andWhere('p.company = :company')
                        ->andWhere('p.isActive = :active')
                        ->setParameter('company', $company)
                        ->setParameter('active', true)
                        ->orderBy('p.name', 'ASC');
                },
                'attr' => [
                    'class' => 'form-control product-select',
                    'data-target-name' => 'productName',
                    'data-target-description' => 'description',
                    'data-target-price' => 'unitPrice',
                    'data-target-unit' => 'unit'
                ]
            ])
            ->add('productName', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom du produit ou service'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Description détaillée'
                ]
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Prix unitaire',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control price-input',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control quantity-input',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '1'
                ]
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unité',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'unité'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceItem::class,
        ]);
    }
}