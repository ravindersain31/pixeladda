<?php

namespace App\Component\Customer;

use App\Entity\Store;
use App\Enum\StoreConfigEnum;
use App\Form\ExclusiveOfferType;
use App\Service\StoreInfoService;
use App\Service\SubscriberService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "ExclusiveOfferForm",
    template: "components/exclusive-offer.html.twig"
)]
class ExclusiveOfferForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public ?bool $isSuccessful = false;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly RequestStack $requestStack,
        private readonly SubscriberService $subscriberService,
        private readonly EntityManagerInterface $entityManager,
        private readonly StoreInfoService $storeInfoService,

    )
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ExclusiveOfferType::class);
    }

    #[LiveAction]
    public function exclusiveOffer(Request $request): void
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->isSuccessful = true;

        try {
            $this->sendExclusiveOfferEmail($data['email']);
            $store = is_array($request->get('store')) ? $request->get('store')['id'] : 1;
            $this->subscriberService->subscribe(
                email: $data['email'],
                type: SubscriberService::ENQUIRY_SAVE_OFFER,
                offers: true,
                store: $this->entityManager->getReference(Store::class, $store),
            );
            $this->flashMessage = 'Thank you for subscribing! Please check your email for updates.';
            $this->resetForm();
        } catch (\Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
        $this->dispatchBrowserEvent('flash:hide');
    }

    private function sendExclusiveOfferEmail(string $userEmail): void
    {
        $storeName = $this->storeInfoService->getStoreName();
        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->subject("Thank You for Subscribing! Save 10% Today!");
        $email->to($userEmail);
        $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->htmlTemplate('emails/exclusive_offer.html.twig')->context([
            'show_unsubscribe_link' => true,
            'user_email' => $userEmail
        ]);
        $this->mailer->send($email);
    }

}