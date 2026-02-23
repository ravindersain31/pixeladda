<?php

namespace App\Component\Customer;

use App\Entity\Store;
use App\Form\Page\UnsubscribeUserType;
use App\Service\SubscriberService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "UnsubscribeUserForm",
    template: "components/unsubscribe_user.html.twig"
)]
class UnsubscribeUserForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';

    private ?string $email = null;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly SubscriberService $subscriberService
    ){
        $this->email = $this->requestStack->getCurrentRequest()->query->get('email');
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(UnsubscribeUserType::class, ['email' => $this->email]);
    }

    #[LiveAction]
    public function unsubscribeAction()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $unsubscribeOptions = $data['unsubscribe'] ?? [];
        $offers = in_array('offers', $unsubscribeOptions) ? true : false;
        $marketing = in_array('marketing_emails', $unsubscribeOptions) ? true : false;

        try {
            $request = $this->requestStack->getCurrentRequest();
            $store = is_array($request->get('store')) ? $request->get('store')['id'] : 1;
            $result = $this->subscriberService->unsubscribe(
                email: $data['email'],
                marketing: $marketing,
                offers: $offers,
                store: $this->entityManager->getReference(Store::class, $store),
            );

            $this->flashError = $result['status'];
            $this->flashMessage = $result['message'];
            $this->dispatchBrowserEvent('flash:hide');
            $this->resetForm();

        } catch (Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
     }
}