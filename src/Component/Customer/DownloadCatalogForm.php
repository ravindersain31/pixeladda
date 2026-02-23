<?php

namespace App\Component\Customer;

use App\Enum\StoreConfigEnum;
use App\Form\Page\SaveAndSubscribeType;
use App\Service\SubscriberService;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use App\Entity\Store;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "DownloadCatalogForm",
    template: "components/download_catalog.html.twig"
)]
class DownloadCatalogForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly SubscriberService $subscriberService,
        private readonly StoreInfoService $storeInfoService,
    ){
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(SaveAndSubscribeType::class);
    }

    #[LiveAction]
    public function subscribeAction()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        try {
            $request = $this->requestStack->getCurrentRequest();
            $store = is_array($request->get('store')) ? $request->get('store')['id'] : 1;
            $this->sendCustomerEmail($data['email']);
            $this->sendAdminEmail($data['email']);
            $this->subscriberService->subscribe(
                email: $data['email'],
                type: SubscriberService::ENQUIRY_SAVE_OFFER,
                marketing: true,
                offers: true,
                store: $this->entityManager->getReference(Store::class, $store),
            );
            $this->flashMessage = 'Thank you for subscribing! Please check your email for updates.';
            $this->dispatchBrowserEvent('flash:hide');
            $this->resetForm();

        } catch (Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }

    }

    private function sendCustomerEmail(string $userEmail): void
    {
        $storeTitle = $this->storeInfoService->storeInfo()['storeName'];
        $storeName = $this->storeInfoService->getStoreName();
        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->subject($storeTitle . " Catalog Request Received!");
        $email->to($userEmail);
        $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->htmlTemplate('emails/save_and_subscribe.html.twig')->context([
            'show_unsubscribe_link' => true,
            'user_email' => $userEmail
        ]);
        $this->mailer->send($email);
    }

    private function sendAdminEmail(string $userEmail): void
    {
        $email = (new TemplatedEmail());
        $storeName = $this->storeInfoService->getStoreName();
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->subject("Contact request from " . $userEmail);
        $email->to(StoreConfigEnum::ADMIN_EMAIL);
        $email->htmlTemplate('emails/subscribe.html.twig')->context([
            'userEmail' => $userEmail,
        ]);
        $this->mailer->send($email);
    }
}