<?php

namespace App\Form;

use App\Entity\Episode;
use App\Entity\Season;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EpisodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add(
                'number',
                ChoiceType::class,
                [
                    'label' => 'Numéro D\'Épisode',
                    'choices' => range(1, 30),
                    'choice_label' => function ($value) {
                        return 'Épisode n° ' . $value;
                    },
                    'row_attr' => [
                        'class' => 'form-row-split my-1 py-2', /* 'input-group' 'form-row-split' 'form-floating' */
                    ],
                ]
            )
            ->add(
                'synopsis',
                TextareaType::class,
                [
                    'label' => 'Synopsys',
                    'attr' => [
                        'placeholder' => 'Synopsis de l\'épisode...',
                        'style' => 'height:20vh'
                    ],
                    'row_attr' => [
                        'class' => 'form-row-split my-1 py-2', /* 'input-group' 'form-row-split' 'form-floating' */
                    ],
                ]
            )
            ->add(
                'season',
                EntityType::class,
                [
                    'label' => 'Saison',
                    'class' => Season::class,
                    'attr' => [
                        'class' => 'form-select-lg mb-3',
                    ],
                    'choice_label' => 'number',

                    // used to render a select box, check boxes or radios
                    // 'multiple' => true,
                    // 'expanded' => true,
                    'row_attr' => [
                        'class' => 'form-row-split my-1 py-2', /* 'input-group' 'form-row-split' 'form-floating' */
                    ],
                ],
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Episode::class,
        ]);
    }
}