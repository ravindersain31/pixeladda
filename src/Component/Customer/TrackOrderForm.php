<?php

namespace App\Component\Customer;

use App\Entity\Order;
use App\Form\TrackOrderType;
use App\Service\CartManagerService;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "TrackOrderForm",
    template: "components/track_order.html.twig"
)]
class TrackOrderForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public bool $isSuccessful = false;
    public bool $flashError = false;
    public array $orders = [];

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly MailerInterface $mailer, private readonly CartManagerService $cartManagerService, private readonly StoreInfoService $storeInfoService)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(TrackOrderType::class);
    }

    #[LiveAction]
    public function trackOrder()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $isPromoStore = $this->storeInfoService->storeInfo()['isPromoStore'];
        $orderRepo = $this->entityManager->getRepository(Order::class);

        if ($data['orderId']) {
            $order = $orderRepo->findTrackOrder($data['orderId'], $isPromoStore);
        } else if ($data['trackingId']) {
           $order = $orderRepo->findOrdersByTrackingNumber($data['trackingId'], $isPromoStore);
        } else {
           $order = $orderRepo->findOrderByEmailTelephone($data['email'], $data['telephone'], $isPromoStore);
        }

        try {
            if (empty($order)) {
                $this->flashError = false;
                $this->flashMessage = "No order found. Please try again. For questions, please call +1 877-958-1499, message us on our live chat, or email " . $this->storeInfoService->storeInfo()['storeSupportEmail'] . ".";
            } else {
                $this->flashError = false;
                $this->flashMessage = 'Order Successfully Tracked.';
                $this->isSuccessful = true;
                $this->resetForm();
                if (is_array($order)) {
                    $this->orders = $order;
                } else {
                    return $this->redirectToRoute('track_order_details', [
                        'oid' => $order->getOrderId(),
                    ]);
                }
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }

        $this->isSuccessful = true;

    }

    private function handleException(Exception $exception)
    {
        $this->flashError = true;
        $this->flashMessage = $exception->getMessage();
    }
}