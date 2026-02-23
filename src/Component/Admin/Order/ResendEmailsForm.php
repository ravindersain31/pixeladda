<?php

namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Form\Admin\Order\ResendEmailsType;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use App\Enum\StoreConfigEnum;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Event\OrderShippedEvent;
use App\Event\OrderCancelledEvent;
use App\Service\StoreInfoService;

#[AsLiveComponent(
  name: "ResendEmailsForm",
  template: "admin/components/order/resend_emails.html.twig"
)]
class ResendEmailsForm extends AbstractController
{
  use DefaultActionTrait;
  use LiveCollectionTrait;
  use ComponentWithFormTrait;
  use ValidatableComponentTrait;
  use ComponentToolsTrait;


  #[LiveProp]
  public ?string $flashMessage = null;
  public ?string $flashError = 'success';
  public bool $isSuccessful = false;

  #[LiveProp]
  #[NotNull]
  public Order $order;

  public function __construct(
    private readonly EntityManagerInterface $entityManager,
    private readonly OrderService $orderService,
    private readonly MailerInterface $mailer,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly StoreInfoService $storeInfoService,
  ) {}

  protected function instantiateForm(): FormInterface
  {
    return $this->createForm(ResendEmailsType::class, [], [
      'order' => $this->order
    ]);
  }
  public function hasValidationErrors(): bool
  {
    return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
  }

  #[LiveAction]
  public function send(): void
  {
    $this->validate();
    $this->submitForm();
    $form = $this->getForm();
    $data = $form->getData();
    $this->isSuccessful = true;

    try {
      $order = $this->order;

      $res = $this->sendEmails($data['emailTypes'], $order);

      $successMessages = [];
      $errorMessages = [];

      foreach ($res as $emailType => $status) {
        if (str_contains($status, 'success')) {
          $successMessages[] = $emailType;
        } elseif (str_contains($status, 'error')) {
          $errorMessages[] = "$emailType: " . str_replace('error: ', '', $status);
        }
      }

      if (!empty($successMessages)) {
        $this->flashMessage = 'Emails sent successfully for: ' . implode(', ', $successMessages);
        $this->flashError = 'success';
      }

      if (!empty($errorMessages)) {
        $this->flashMessage = 'Errors occurred for: ' . implode(', ', $errorMessages);
        $this->flashError = 'danger';
      }
    } catch (\Exception $e) {
      $this->flashMessage = $e->getMessage();
      $this->flashError = 'danger';
    }
  }

  private function sendEmails(array $emailTypes, Order $order): array
  {
    $results = [];

    foreach (ResendEmailsType::EMAILS_TYPES as $emailType => $emailLabel) {
      try {
        if (in_array($emailType, $emailTypes)) {
          $methodName = 'send' . str_replace('_', '', ucwords(strtolower($emailType), '_')) . 'Email';
          if (method_exists($this, $methodName)) {
            $this->$methodName($order);
            $results[$emailLabel] = 'success';
          } else {
            $results[$emailLabel] = 'error: Method not implemented';
          }
        }
      } catch (\Exception $e) {
        $results[$emailLabel] = 'error: ' . $e->getMessage();
      }
    }

    return $results;
  }


  private function sendOrderReceivedEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $email = new TemplatedEmail();
    $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $email->subject("Order Received #" . $order->getOrderId());
    $email->to($this->getEmail($order));
    $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $email->htmlTemplate('emails/order_received.html.twig')->context([
      'order' => $order,
      'store_url' => StoreConfigEnum::STORE_URL,
      'store_name' => $storeName,
      'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
    ]);

    $this->mailer->send($email);
  }

  private function sendProofApprovedEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $approvedProofMessage = new TemplatedEmail();
    $approvedProofMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $approvedProofMessage->subject("Proof Approved for Order ID #" . $order->getOrderId());
    $approvedProofMessage->to($this->getEmail($order));
    $approvedProofMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $approvedProofMessage->htmlTemplate('emails/order_proof_approved.html.twig')->context([
      'order' => $order,
      'store_url' => StoreConfigEnum::STORE_URL,
      'store_name' => $storeName,
      'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
      'message' => $order->getApprovedProof(),
    ]);

    $this->mailer->send($approvedProofMessage);
  }

  private function sendProofUploadedEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $proofUploadedMessage = new TemplatedEmail();
    $proofUploadedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $proofUploadedMessage->subject("New Proof Uploaded for Order ID #" . $order->getOrderId());
    $proofUploadedMessage->to($this->getEmail($order));
    $proofUploadedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $proofUploadedMessage->htmlTemplate('emails/order_new_proof.html.twig')->context([
      'order' => $order,
      'store_url' => StoreConfigEnum::STORE_URL,
      'store_name' =>  $storeName,
      'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
      'message' => $order->getOrderMessages()->last(),
    ]);

    $this->mailer->send($proofUploadedMessage);
  }

  private function sendChangeRequestedEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $changesRequestedMessage = new TemplatedEmail();
    $changesRequestedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
    $changesRequestedMessage->subject("Changes Requested for Order ID #" . $order->getOrderId());
    $changesRequestedMessage->to($this->getEmail($order));
    $changesRequestedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
    $changesRequestedMessage->htmlTemplate('emails/order_changes_requested.html.twig')->context([
      'order' => $order,
      'message' => $order->getOrderMessages()->last(),
    ]);

    $this->mailer->send($changesRequestedMessage);
  }

  private function sendOrderShippedEmail(Order $order): void
  {
    $this->eventDispatcher->dispatch(new OrderShippedEvent($order), OrderShippedEvent::NAME);
  }


  private function sendOrderCancelledEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $shippedMessage = new TemplatedEmail();
    $shippedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
    $shippedMessage->subject("Order Cancelled #" . $order->getOrderId());
    $shippedMessage->to($this->getEmail($order));
    $shippedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
    $shippedMessage->htmlTemplate('emails/order_cancelled.html.twig')->context([
      'order' => $order,
    ]);

    $this->mailer->send($shippedMessage);
  }

  private function sendOrderOutForDeliveryEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $email = new TemplatedEmail();
    $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $email->subject("Your Order is Out for Delivery #" . $order->getOrderId());
    $email->to($this->getEmail($order));
    $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $email->htmlTemplate('emails/order_out_for_delivery.html.twig')->context([
      'order' => $order,
    ]);

    $this->mailer->send($email);
    $order->setMetaDataKey('isOutForDeliveryEmailSent', true);
  }

  private function sendOrderDeliveredEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $email = new TemplatedEmail();
    $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $email->subject("Your Order has been Delivered #" . $order->getOrderId());
    $email->to($this->getEmail($order));
    $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $email->htmlTemplate('emails/order_delivered.html.twig')->context([
      'order' => $order,
    ]);

    $this->mailer->send($email);
    $order->setMetaDataKey('isDeliveredEmailSent', true);
  }

  private function sendOrderRefundedEmail(Order $order): void
  {
    $storeName = $this->storeInfoService->getStoreName();
    $shippedMessage = new TemplatedEmail();
    $shippedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $shippedMessage->subject("Order Cancelled #" . $order->getOrderId());
    $shippedMessage->to($this->getEmail($order));
    $shippedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
    $shippedMessage->htmlTemplate('emails/order_cancelled.html.twig')->context([
      'order' => $order,
    ]);

    $this->mailer->send($shippedMessage);
  }

  private function getEmail(Order $order): string
  {
    $billingAddress = $order->getBillingAddress();
    $orderEmail = $billingAddress['email'];
    if (!$orderEmail) {
      $orderEmail = $order->getUser()->getEmail();
    }
    return $orderEmail;
  }
}
