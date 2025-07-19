<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de client',
                'choices' => Customer::getTypeChoices(),
                'attr' => [
                    'class' => 'form-control customer-type-select',
                    'data-target' => 'company-fields'
                ],
                'placeholder' => 'Choisir le type'
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prénom du contact'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de famille'
                ]
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Nom de la société',
                'required' => false,
                'attr' => [
                    'class' => 'form-control company-field',
                    'placeholder' => 'Raison sociale de l\'entreprise'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'adresse@email.com'
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '01 23 45 67 89'
                ]
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Numéro et nom de rue'
                ]
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '75001',
                    'maxlength' => 10
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Paris'
                ]
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'data' => 'France',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'attr' => [
                    'class' => 'form-control company-field',
                    'placeholder' => 'Numéro SIRET (14 chiffres)',
                    'maxlength' => 14
                ]
            ])
            ->add('vatNumber', TextType::class, [
                'label' => 'Numéro de TVA',
                'required' => false,
                'attr' => [
                    'class' => 'form-control company-field',
                    'placeholder' => 'FR12345678901'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Client actif',
                'required' => false,
                'data' => true,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);

        // Event listener pour ajuster les champs selon le type
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $customer = $event->getData();
            $form = $event->getForm();

            if ($customer && $customer->getType() === Customer::TYPE_COMPANY) {
                // Rendre les champs entreprise obligatoires
                $form->add('companyName', TextType::class, [
                    'label' => 'Nom de la société',
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control company-field',
                        'placeholder' => 'Raison sociale de l\'entreprise'
                    ]
                ]);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['type']) && $data['type'] === Customer::TYPE_COMPANY) {
                // Rendre les champs entreprise obligatoires
                $form->add('companyName', TextType::class, [
                    'label' => 'Nom de la société',
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control company-field',
                        'placeholder' => 'Raison sociale de l\'entreprise'
                    ]
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}