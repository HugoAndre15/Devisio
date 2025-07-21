<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $builder->getData() && $builder->getData()->getId();

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prénom de l\'utilisateur'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de famille'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'adresse@email.com'
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Employé' => 'ROLE_EMPLOYE',
                    'Comptable' => 'ROLE_COMPTABLE',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Sélectionnez un ou plusieurs rôles pour cet utilisateur.'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Utilisateur actif',
                'required' => false,
                'data' => $isEdit ? null : true, // Actif par défaut pour nouveaux utilisateurs
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Les utilisateurs inactifs ne peuvent pas se connecter.'
            ]);

        // Mot de passe obligatoire pour nouveaux utilisateurs, optionnel pour modification
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => !$isEdit,
            'first_options' => [
                'label' => $isEdit ? 'Nouveau mot de passe' : 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $isEdit ? 'Laisser vide pour ne pas changer' : 'Mot de passe'
                ]
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Confirmer le mot de passe'
                ]
            ],
            'invalid_message' => 'Les mots de passe ne correspondent pas.',
            'constraints' => [
                new Length([
                    'min' => 6,
                    'minMessage' => 'Le mot de passe doit comporter au moins {{ limit }} caractères',
                    'max' => 4096,
                ]),
            ] + ($isEdit ? [] : [new NotBlank(['message' => 'Veuillez saisir un mot de passe'])]),
            'help' => $isEdit ? 'Laissez vide si vous ne souhaitez pas changer le mot de passe.' : 'Minimum 6 caractères.'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}