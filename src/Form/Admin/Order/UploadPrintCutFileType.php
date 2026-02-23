<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Helper\VichS3Helper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Vich\UploaderBundle\Form\Type\VichFileType;

class UploadPrintCutFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('printFile', VichFileType::class, [
            'required' => false,
            'label' => 'Print File',
            'allow_delete' => false,
        ]);

        $builder->add('cutFile', VichFileType::class, [
            'required' => false,
            'label' => 'Cut File',
            'allow_delete' => false,
        ]);

        $builder->add('vectorFile', VichFileType::class, [
            'required' => false,
            'label' => 'Vector File',
            'allow_delete' => false,
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Upload Files',
            'row_attr' => [
                'class' => 'mb-0'
            ],
            'attr' => [
                'class' => 'btn btn-primary btn-sm'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => Order::class,
        ]);
    }
}
