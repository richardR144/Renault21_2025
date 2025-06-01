<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Piece;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class InsertPieceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])

            ->add('name')
            

            ->add('description')
            ->add('exchange', ChoiceType::class, [
                'label' => "Type d'annonce",
                'choices'  => [
                    'Vente' => 'vente',
                    'Échange' => 'echange',
        ],
                'expanded' => true,
                'multiple' => false,

            ])
            
            ->add('price')
            ->add('image', FileType::class, [
                'label' => 'Image (format : jpg, png, jpeg, gif, webp)',
                'mapped' => false,
                'required' => false,
            ])

            ->add('valider', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Piece::class,
        ]);
    }
}
