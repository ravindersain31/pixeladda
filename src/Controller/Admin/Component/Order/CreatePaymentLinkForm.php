<?php

namespace App\Controller\Admin\Component\Order;


use App\Entity\Currency;
use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\StoreConfigEnum;
use App\Form\Admin\Order\CreatePaymentLinkType;
use App\Helper\PromoStoreHelper;
use App\Service\OrderLogger;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "CreatePaymentLinkForm",
    template: "admin/components/create-payment-link-form.html.twig"
)]
class CreatePaymentLinkForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderLogger            $orderLogger,
        private readonly MailerInterface        $mailer,
        private readonly PromoStoreHelper       $promoStoreHelper,
        private readonly StoreInfoService       $storeInfoService,
    )
    {
    }

    #[LiveProp]
    public ?Order $order = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CreatePaymentLinkType::class);
    }

    #[LiveAction]
    public function save(): Response
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']);

        $paymentLink = new OrderTransaction();
        $paymentLink->setOrder($this->order);
        $paymentLink->setAmount($data['amount']);
        $paymentLink->setCurrency($currency);
        $paymentLink->setStatus(PaymentStatusEnum::INITIATED);
        $paymentLink->setPaymentMethod(PaymentMethodEnum::CREDIT_CARD);
        $paymentLink->setIsPaymentLink(true);
        $paymentLink->setMetaDataKey('internalNote', $data['internalNote']);
        $paymentLink->setMetaDataKey('customerNote', $data['customerNote']);

        $this->entityManager->persist($paymentLink);

        $this->order->setPaymentLinkAmount($this->order->getPaymentLinkAmount() + $paymentLink->getAmount());
        $this->entityManager->persist($this->order);

        $this->entityManager->flush();

        $paymentLinkUrl = $this->generateUrl('payment_link', ['requestId' => $paymentLink->getTransactionId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $paymentLinkUrl = $this->promoStoreHelper->storeBasedUrl($paymentLinkUrl, $this->order->getStoreDomain());
        $storeName = $this->storeInfoService->getStoreName();

        $email = new TemplatedEmail();
        $email->to($this->order->getUser()->getEmail());
        $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->subject('Payment Request Received For Order #' . $this->order->getOrderId());
        $email->htmlTemplate('emails/payment_link.html.twig')->context([
            'order' => $this->order,
            'transaction' => $paymentLink,
            'paymentLinkUrl' => $paymentLinkUrl
        ]);

        $this->mailer->send($email);

        $this->orderLogger->setOrder($this->order);
        $this->orderLogger->log('Payment link created and sent to customer<br/> Url: <a href="' . $paymentLinkUrl . '" target="_blank">' . $paymentLinkUrl . '</a>', $this->getUser());

        $this->addFlash('success', 'Payment link created and sent to customer');
        return $this->redirectToRoute('admin_order_transactions', ['orderId' => $this->order->getOrderId()]);
    }

}
