<?php

namespace App\Component\Admin;

use App\Entity\StoreSettings;
use App\Entity\Store;
use App\Form\Admin\Configuration\StoreSettingsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminStoreSettingsForm",
    template: "admin/components/store-settings-form.html.twig"
)]
class StoreSettingsForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?StoreSettings $settings;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            StoreSettingsType::class,
            $this->settings
        );
    }
}
