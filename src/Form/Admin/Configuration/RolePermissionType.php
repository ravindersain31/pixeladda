<?php

namespace App\Form\Admin\Configuration;

use App\Entity\RolePermission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class RolePermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'constraints' => [
              new Regex([
                  'pattern' => '/^[a-z0-9_:]+$/',
                  'message' => 'Only lowercase letters, numbers and underscores, colon are allowed.',
              ]),
            ],
            'attr' => [
                'data-modal' => 'name'
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RolePermission::class,
        ]);
    }
}
