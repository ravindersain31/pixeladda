<?php

namespace App\Form\Admin\Customer;

use App\Entity\User;
use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;

class UpdateEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order **/
        $order = $options['data']['order'];

        $builder ->add('email', Type\TextType::class, [
            'label' => 'Email',
            'required' => true,
            'data' => $order->getUser()->getEmail(),
            'attr' => ['class' => 'custom-class'],
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Email(),
                new Constraints\Length(['max' => 255]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => Order::class,
        ]);
    }
}
