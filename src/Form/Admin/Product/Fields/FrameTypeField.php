<?php

namespace App\Form\Admin\Product\Fields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Constant\Editor\Addons;
use Symfony\Component\Validator\Constraints\NotBlank;

class FrameTypeField extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $frameTypes = array_filter(Addons::getFrameTypes(), fn($type) => $type !== Addons::FRAME_NONE);
        $editData = isset($options['data']) && is_array($options['data']) && !empty($options['data']) ? $options['data'] : null;
        foreach ($frameTypes as $frameType) {
            $builder->add(
                $frameType,
                IntegerType::class,
                [
                    'label' => Addons::getFrameDisplayText($frameType),
                    'attr' => [
                        'placeholder' => 'Enter Quantity',
                        'inputmode' => 'numeric',
                        'data-numeric-input' => true,
                    ],
                    'required' => false,
                    'mapped' => false,
                    'data' => isset($editData) ? $editData[$frameType] ?? null : null,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please specify the total number of frames.',
                        ]),
                    ],
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
