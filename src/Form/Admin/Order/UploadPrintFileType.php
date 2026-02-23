<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Helper\VichS3Helper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Vich\UploaderBundle\Form\Type\VichFileType;

class UploadPrintFileType extends AbstractType
{
    public function __construct(private readonly VichS3Helper $vichS3Helper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('printFileFile', VichFileType::class, [
            'required' => false,
            'label' => 'Print File',
            'allow_delete' => false,
        ]);
        $builder->add('cutFileFile', VichFileType::class, [
            'required' => false,
            'label' => 'Cut File',
            'allow_delete' => false,
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Upload Files',
            'attr' => [
                'class' => 'btn btn-primary'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
