<?php

namespace App\Component\Customer;

use App\Entity\AppUser;
use App\Entity\UserFile;
use App\Entity\WholeSeller;
use App\Enum\RolesEnum;
use App\Enum\StoreConfigEnum;
use App\Enum\WholeSellerEnum;
use App\Form\CreateAccountType;
use App\Helper\PromoStoreHelper;
use App\Repository\UserRepository;
use App\Repository\WholeSellerRepository;
use App\Repository\StoreDomainRepository;
use App\Service\ReferralService;
use App\Service\StoreInfoService;
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
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsLiveComponent(
    name: "WholeSellerCreateAccountForm",
    template: "components/whole_seller_create_account.html.twig"
)]
class WholeSellerCreateAccountForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;
    public function __construct(
        private StoreInfoService $storeInfoService,
        private WholeSellerRepository $wholeSellerRepository,
        private EntityManagerInterface $entityManager,
        private PromoStoreHelper $promoStoreHelper,
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private RequestStack $requestStack,
        private ReferralService $referralService,
        private TokenStorageInterface $tokenStorage,
        private StoreDomainRepository  $storeDomainRepository,

    ) {}

    #[LiveProp]
    public bool $isSuccessful = false;

    #[LiveProp]
    public bool $showUploadField = false;
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';

    protected function instantiateForm(): FormInterface
    {
        $referralCode = $this->requestStack->getCurrentRequest()->query->get('referralCode');
        $request = $this->requestStack->getCurrentRequest();

        if (!$this->showUploadField) {
            $this->showUploadField = $request->getPathInfo() === WholeSellerEnum::WHOLE_SELLER_CREATE_ACCOUNT_PATH->value;
        }
        $user = $this->getUser();
        if( $user){
            $fullName = $user->getName();
            $parts = preg_split('/\s+/', trim($fullName), 2);
            $firstName = $parts[0] ?? '';
            $lastName  = $parts[1] ?? '';
        }

        $formData = null;

        if ($user instanceof AppUser) {
            $formData = new AppUser();
            $formData->setEmail($user->getEmail());
            $formData->setFirstName($firstName);
            $formData->setLastName($lastName);
        }
        return $this->createForm(
            CreateAccountType::class,
            $formData,
            [
                'referralCode' => $referralCode,
                'showUploadField' => $this->showUploadField,
            ]
        );
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function CreateAccount(Request $request)
    {
        $this->submitForm();
        $form = $this->getForm();


        /** @var array<string,mixed>|null $filesForForm */
        $formName = $form->getName();
        $filesForForm = $request->files->get($formName);
        $uploadedFile = $filesForForm['wholeSellerImageFile'] ?? null;
        if (!$form->isSubmitted() || !$form->isValid()) {
            return;
        }

        $data =$form->getData();
        $email = $data->getEmail();
        $user = $this->getUser();
        if ($user && $email !== $user->getEmail()) {
            $this->tokenStorage->setToken(null);
            $request->getSession()?->invalidate();
        }
        
        $host = $this->storeInfoService->storeInfo()['storeHost'];
        $storeDomain = $this->storeDomainRepository->findByDomain($host);

        $existingUser = $this->userRepository->findOneBy(['username' => $email]);

        if ($existingUser) {
            $roles = $existingUser->getRoles();
            if (in_array(RolesEnum::USER->value, $roles, true) && in_array(RolesEnum::WHOLE_SELLER->value, $roles, true)) {
                $this->addFlash('danger', "We have already registered an account with this email. Please Sign In to your account or use another email.");
                return $this->redirectToRoute('login');
            }
        }

        if ($existingUser) {
            
            $customer = $existingUser;
            $referralCode = $data->getReferralCode() ?? null;
            $customer->setIsEnabled(true);
            $customer->setWholeSellerStatus(WholeSellerEnum::PENDING);
            $customer->setUsername((string)$data->getEmail());
            $customer->setName($data->getfirstName() . ' ' . $data->getlastName());
            $customer->setRoles([RolesEnum::WHOLE_SELLER->value,RolesEnum::USER->value]);
            $customer->setPassword($data->getPassword());
            $customer->setEmail($data->getEmail());
            $customer->setReferralCode($referralCode);
            $customer->setFirstName($data->getFirstName());
            $customer->setLastName($data->getLastName());
            $customer->setPassword($data->getPassword());
            $customer->setIsEnabled(true);
        } else {
            $customer = new AppUser();
            $customer->setIsEnabled(true);
            $customer->setUsername($email);
            $customer->setName($data->getFirstName() . ' ' . $data->getLastName());
            $customer->setEmail($email);
            $customer->setPassword($data->getPassword());
            $customer->setRoles([RolesEnum::WHOLE_SELLER->value, RolesEnum::USER->value]);
            $customer->setReferralCode($data->getReferralCode());
            $this->userRepository->save($customer);
        }
            $fields = ['city','country', 'state','zipcode','mobile', 'address', 'companyName', 'aboutCompany', 'website', 'clientType', 'hearAboutUs', ];
            $data = [];
            foreach ($fields as $field) {
                $data[$field] = $form->get($field)->getData();
            }
            $customerWholeSeller = $this->wholeSellerRepository->findOneBy(['appUser' => $customer]) ?? new WholeSeller();
            $customerWholeSeller->setAppUser($customer);
            $customerWholeSeller->setCity($data['city']);
            $customerWholeSeller->setCountry($data['country']);
            $customerWholeSeller->setState($data['state']);
            $customerWholeSeller->setZipcode($data['zipcode']);
            $customerWholeSeller->setMobile($data['mobile']);
            $customerWholeSeller->setAddress($data['address']);
            $customerWholeSeller->setCompanyName($data['companyName']);
            $customerWholeSeller->setAboutCompany($data['aboutCompany']);
            $customerWholeSeller->setWebsite($data['website']);
            $customerWholeSeller->setClientType($data['clientType']);
            $customerWholeSeller->setHearAboutUs($data['hearAboutUs']);
            $customerWholeSeller->setStoreDomain($storeDomain);
            $this->wholeSellerRepository->save($customerWholeSeller);

            if ($uploadedFile instanceof UploadedFile) {
                $wholeSellerFile = $this->handleFileUpload(
                    $uploadedFile,
                    'WHOLE_SELLER',
                    $customerWholeSeller,
                );
                $customerWholeSeller->setWholeSellerImageFile($wholeSellerFile);
            }

            $this->entityManager->flush();

            $this->flashMessage = 'Your wholesaler account verification is in progress. Please wait for approval.';
            $this->isSuccessful = true;
            $this->addFlash('success', $this->flashMessage);
            return $this->redirectToRoute('whole_seller_login');

    }
    
    private function handleFileUpload( UploadedFile $file, string $type, WholeSeller $wholeSeller ): UserFile {
        $userFile = new UserFile();
        $userFile->setFileObject($file);
        $userFile->setType($type);
        $userFile->setUploadedBy($wholeSeller->getAppUser());
        $this->entityManager->persist($userFile);
        return $userFile;
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