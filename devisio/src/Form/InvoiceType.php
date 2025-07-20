<?php

namespace App\Form;

use App\Entity\Invoice;
use App\Entity\Customer;
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

class InvoiceType extends AbstractType
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
                    'placeholder' => 'Sujet de la facture'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description détaillée de la facture'
                ]
            ])
            ->add('invoiceDate', DateType::class, [
                'label' => 'Date de facture',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Date d\'échéance',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => InvoiceItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'attr' => [
                    'class' => 'invoice-items-collection'
                ]
            ])
            ->add('paymentTerms', TextareaType::class, [
                'label' => 'Conditions de paiement',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Conditions de paiement (ex: paiement sous 30 jours)'
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}