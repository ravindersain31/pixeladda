<?php

namespace App\Form\Admin\Product\Fields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ShippingFields extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $domains = $options['domains'];

        $totalDomains = count($domains) + 1;
        $colSize = 12 / $totalDomains;
        if (in_array($colSize, [5, 7])) {
            $colSize = 6;
        }

        $builder->add('qty', Type\TextType::class, [
            'label' => 'From Qty',
            'row_attr' => ['class' => 'col-' . $colSize],
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Regex([
                    'pattern' => '/^[0-9]+$/',
                    'message' => 'Please enter a valid number',
                ]),
            ],
        ]);

        foreach ($domains as $domain) {
            $currency = $domain->getCurrency();
            $builder->add(strtolower($currency->getCode()), Type\TextType::class, [
                'label' => 'Price (' . $currency->getCode() . ')',
                'row_attr' => ['class' => 'col-' . $colSize]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('domains');
        $resolver->setDefaults([
        ]);
    }

}
