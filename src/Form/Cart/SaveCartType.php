<?php

namespace App\Form\Cart;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;

class SaveCartType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager){

    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', Type\TextType::class, [
            'label' => 'Email',
            'attr' => ['class' => 'form-control save-cart-email', 'placeholder' => 'Enter your email address','data-model' => "email"],
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Email(),
            ],
        ]);

        $builder->add('cartId', Type\HiddenType::class, [
            'data' => $options['data']['cartId'],
            'attr' => ['class' => 'form-control save-cart-cartId'],
        ]);

        $builder->add('itemId', Type\HiddenType::class, [
            'data' => $options['data']['itemId'],
            'attr' => ['class' => 'form-control save-cart-cartId'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
