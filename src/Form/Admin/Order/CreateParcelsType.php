<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Helper\ParcelGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class CreateParcelsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['order'];
        $shipments = $order->getOrderShipments();

        try {
            $parcelGenerator = new ParcelGenerator();
            $groupedItems = $order->groupedItemsQtyBySizes();
            $defaultParcels = $parcelGenerator->generateDefaultParcels($groupedItems);
        } catch (\Exception $e) {
            $defaultParcels = [];
        }

        if (count($shipments) > 0) {
            $defaultParcels = [];
        }

        $builder->add('parcels', LiveCollectionType::class, [
            'entry_type' => CreateParcelType::class,
            'required' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'row_attr' => ['class' => 'd-none'],
            'button_add_options' => [
                'label' => 'Add Box',
                'attr' => ['class' => 'btn btn-link p-0 btn-sm'],
            ],
            'button_delete_options' => [
                'label' => 'X',
                'attr' => ['class' => 'btn btn-danger btn-sm ms-2'],
            ],
            'data' => $defaultParcels,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_protection' => false]);
        $resolver->setRequired('order');
    }
}