<?php

namespace App\Form;

use App\Entity\Quote;
use App\Entity\Customer;
use App\Entity\DiscountCode;
use App\Repository\DiscountCodeRepository;
use App\Repository\CustomerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class QuoteType extends AbstractType
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
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'displayName',
                'label' => 'Client',
                'placeholder' => 'Sélectionner un client',
                'query_builder' => function (CustomerRepository $er) use ($company) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.company = :company')
                        ->andWhere('c.isActive = :active')
                        ->setParameter('company', $company)
                        ->setParameter('active', true)
                        ->orderBy('c.lastName', 'ASC');
                },
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Sujet du devis'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description détaillée du devis'
                ]
            ])
            ->add('quoteDate', DateType::class, [
                'label' => 'Date du devis',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('validUntil', DateType::class, [
                'label' => 'Valide jusqu\'au',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => QuoteItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'attr' => [
                    'class' => 'quote-items-collection'
                ]
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Conditions',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Conditions particulières'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes internes'
                ]
            ])
            ->add('discountCodeInput', TextType::class, [
                'label' => 'Code de réduction',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez un code de réduction',
                    'id' => 'discount-code-input'
                ],
                'help' => 'Code optionnel pour appliquer une réduction'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}