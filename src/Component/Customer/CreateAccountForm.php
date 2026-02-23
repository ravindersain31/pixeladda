<?php

namespace App\Component\Customer;

use App\Entity\AppUser;
use App\Enum\StoreConfigEnum;
use App\Form\CreateAccountType;
use App\Helper\PromoStoreHelper;
use App\Repository\UserRepository;
use App\Service\ReferralService;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "CreateAccountForm",
    template: "components/create_account.html.twig"
)]
class CreateAccountForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;
    public function __construct(private StoreInfoService $storeInfoService, private EntityManagerInterface $entityManager, private PromoStoreHelper $promoStoreHelper, private MailerInterface $mailer, private UserRepository $userRepository, private RequestStack $requestStack, private ReferralService $referralService)
    {
    }

    #[LiveProp]
    public bool $isSuccessful = false;
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';

    protected function instantiateForm(): FormInterface
    {
        $referralCode = $this->requestStack->getCurrentRequest()->query->get('referralCode');
       
        return $this->createForm(
            CreateAccountType::class,
            null, 
            ['referralCode' => $referralCode] 
        );
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function CreateAccount()
    {
        $this->submitForm();
        $form = $this->getForm();

        $data =$form->getData();
        $email = $data->getEmail();

        $existingUser = $this->userRepository->findOneBy(['username' => $email]);
        if ($existingUser) {
            $this->addFlash('danger', "We have already registered an account with this email. Please Sign In to your account or use another email.");
            return $this->redirectToRoute('login');
        }
        $customer = new AppUser();
        if ($form->isSubmitted() && $form->isValid()) {
            $data =$form->getData();
            $referralCode = $data->getReferralCode() ?? null;
            $customer->setIsEnabled(true);
            $customer->setUsername((string)$data->getEmail());
            $customer->setName($data->getfirstName() . ' ' . $data->getlastName());
            $customer->setRoles(['ROLE_USER']);
            $customer->setPassword($data->getPassword());
            $customer->setEmail($data->getEmail());
            $customer->setReferralCode($referralCode);
            $this->userRepository->save($customer, true);

            $this->flashMessage = 'Account Created successfully';
            $this->isSuccessful = true;
            $this->addFlash('success', $this->flashMessage);
            return $this->redirectToRoute('login');
        }

    }

    private function sendWelcomeEmail($data){
        $storeName = $this->storeInfoService->getStoreName();
        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->subject("Welcome to ". $storeName);
        $email->to($data->getEmail());
        $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->htmlTemplate('emails/welcome.html.twig')->context([
            'customer' => $data,
        ]);
        $this->mailer->send($email);
    }
}