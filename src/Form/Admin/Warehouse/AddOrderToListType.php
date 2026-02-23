<?php

namespace App\Form\Admin\Warehouse;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Repository\OrderRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddOrderToListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('order', AddOrderIDAutocomplete::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(message: 'Order ID is required')
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired([
            'printerName',
            'shipByList'
        ]);
    }
}
