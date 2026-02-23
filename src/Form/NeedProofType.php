<?php

namespace App\Form;

use App\Entity\Cart;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NeedProofType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Cart $cart **/
        $cart = $options['data'];

        $builder->add('needProof', Type\ChoiceType::class, [
            'label' => 'Do you want to receive a digital proof to approve or request changes before beginning production?',
            'choices' => [
                'Yes, I want to review my proof before approving for production.' => true,
                'No, I approve my design now and would like to begin production immediately.' => false,
            ],
            'data' => $cart->isNeedProof() ?? true,
            'expanded' => true,
            'required' => true,
            'row_attr' => ['class' => 'm-0'] ,
        ]);

        $builder->add('designApproved', Type\HiddenType::class, [
            'data' => $cart->isDesignApproved() ? '1' : '0',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cart::class,
            'csrf_protection' => false,
        ]);
    }
}
