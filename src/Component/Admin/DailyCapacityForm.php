<?php

namespace App\Component\Admin;

use App\Entity\StoreSettings;
use App\Entity\Store;
use App\Form\Admin\Configuration\DailyCapacityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "DailyCapacityForm",
    template: "admin/components/daily-capacity-form.html.twig"
)]
class DailyCapacityForm extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    #[NotNull]
    public ?string $value;

    #[LiveProp]
    public ?string $flashMessage = null;

    public ?string $flashError = null;

    public bool $isSuccessful = false;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            DailyCapacityType::class,['value' => $this->value]
        );
    }

    #[LiveAction]
    public function save(Request $request)
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $value = isset($data['value']) ? (string) $data['value'] : '';
        $this->isSuccessful = true;

        try{
            if ($this->isSuccessful) {
                $this->flashError = "success";
                $this->flashMessage = "Submitted Successfully";
                $store = is_array($request->get('store')) ? $request->get('store')['id'] : 1;
                $dailyCapacity = $this->entityManager->getRepository(StoreSettings::class)->findOneBy(['settingKey' => 'daily_capacity']);

                if (!$dailyCapacity) {
                    $dailyCapacity = new StoreSettings();
                    $dailyCapacity->setSettingKey('daily_capacity');
                    $this->entityManager->persist($dailyCapacity);
                }

                $dailyCapacity->setStore($this->entityManager->getReference(Store::class, $store));
                $dailyCapacity->setValue($value);
                $dailyCapacity->setIsEnabled(true);

                $this->entityManager->flush();

                return $this->redirectToRoute('admin_dashboard');
            } else {
                $this->flashError = "failed";
                $this->flashMessage = "Failed to Submit";
            }


        } catch (\Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
    }
}
