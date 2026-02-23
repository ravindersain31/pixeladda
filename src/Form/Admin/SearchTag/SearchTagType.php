<?php

namespace App\Form\Admin\SearchTag;

use App\Entity\SearchTag;
use App\Entity\Store;
use App\Form\DataTransformer\TagsArrayTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class SearchTagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('urlName', TextType::class, [
            'label' => 'Url Name',
            'required' => true,
            'attr' => [
                'placeholder' => 'eg. https://www.yardsignplus.com/business-ads',
            ],
            'constraints' => [
                new Constraints\NotNull(),
                new Constraints\Url([
                    'requireTld' => true,
                ])
            ]
        ]);

        $builder->add('tags', TextType::class, [
            'label' => 'Search Tags',
            'required' => true,
            'attr' => [
                'data-role' => 'tagsinput',
            ],
            'constraints' => [
                new Constraints\NotNull(),
            ]
        ]);

        $builder->get('tags')->addModelTransformer(new TagsArrayTransformer());

        $builder->add('store', EntityType::class, [
            'class' => Store::class,
            'required' => true,
            'placeholder' => '-- Select Store --',
            'constraints' => [
                new Constraints\NotNull(),
            ]
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'submit',
            'attr' => [
                'class' => 'btn btn-dark',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchTag::class,
        ]);
    }
}
