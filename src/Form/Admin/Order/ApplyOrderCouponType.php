<?php

namespace App\Form\Admin\Order;

use App\Entity\Admin\Coupon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplyOrderCouponType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => 'Coupon Code',
                'class' => 'box-shadow',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $groups = ['Default'];
                if (empty($data['code'])) {
                    $form->get('code')->addError(new FormError('Coupon code is required.'));
                    return $groups;
                }
                $validateCoupon = $this->entityManager->getRepository(Coupon::class)->findOneBy(['code' => $data['code']]);
                if (!$validateCoupon) {
                    $form->get('code')->addError(new FormError('This coupon is incorrect or no longer valid.'));
                    return $groups;
                }
            },

        ]);
    }
}
