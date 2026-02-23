<?php

namespace App\Form\Admin\Customer;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', Type\TextType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'custom-class'],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('mobile', Type\TextType::class, [
                'required' => false,
                'label' => 'Mobile',
                'attr' => ['class' => 'custom-class'],
            ])
            ->add('firstName', Type\TextType::class, [
                'required' => false,
                'label' => 'First Name',
                'attr' => ['class' => 'custom-class'],
            ])
            ->add('lastName', Type\TextType::class, [
                'required' => false,
                'label' => 'Last Name',
                'attr' => ['class' => 'custom-class'],
            ])
            ->add('isEnabled', Type\CheckboxType::class, [
                'label' => 'Is Enabled',
                'required' => false,
            ]);
            //->add('wholeSeller', WholeSellerType::class);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Submit',
            'attr' => ['class' => 'btn btn-dark'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
