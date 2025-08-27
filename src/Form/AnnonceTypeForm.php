<?php

namespace App\Form;

use App\Entity\Annonce;
use App\Entity\Piece;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AnnonceTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'annonce',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le titre de votre annonce'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre annonce en détail'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire']),
                    new Length([
                        'min' => 10,
                        'max' => 10000,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de contact',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre.email@exemple.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire'])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'annonce',
                'choices' => [
                    'Vente' => 'sale',
                    'Échange' => 'exchange',
                    'Achat' => 'purchase'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix (€)',
                'required' => false,
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control']
            ])
            ->add('piece', EntityType::class, [
                'class' => Piece::class,
                'choice_label' => 'name',
                'label' => 'Pièce concernée',
                'placeholder' => 'Sélectionnez une pièce',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une pièce'])
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF, WebP)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5MB'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Annonce::class,
        ]);
    }
}
