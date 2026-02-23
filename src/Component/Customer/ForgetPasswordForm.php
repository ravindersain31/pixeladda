<?php

namespace App\Component\Customer;

use App\Entity\AppUser;
use App\Enum\StoreConfigEnum;
use App\Form\ForgotPasswordType;
use App\Service\CartManagerService;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "ForgetPasswordForm",
    template: "components/forgot_password.html.twig"
)]
class ForgetPasswordForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public bool $isSuccessful = false;
    public bool $flashError = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager, 
        private readonly MailerInterface $mailer, 
        private readonly CartManagerService $cartManagerService, 
        private UserPasswordHasherInterface $passwordHasher,
        private readonly StoreInfoService $storeInfoService,
    ){}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ForgotPasswordType::class);
    }

    #[LiveAction]
    public function forgotPassword()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $user = $this->entityManager->getRepository(AppUser::class)->findOneBy(['email' => $data['email']]);

        if ($user instanceof AppUser) {
            try {
                $newPass = bin2hex(random_bytes(4));
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPass);
                $user->setPassword($hashedPassword);
                $user->setIsTempPass(true);
                $this->sendWelcomeEmail($user->getEmail(), $newPass);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                $this->flashError = true;
                $this->flashMessage = 'We have emailed you a password reset link. Please check your inbox to follow the emailed instructions.';
                $this->resetForm();
            } catch (Exception $e) {
                $this->handleException($e);
            }
        } else {
            $this->flashError = false;
            $this->flashMessage = "This is not a registered email address. Please try again or email " . $this->storeInfoService->storeInfo()['storeSupportEmail'] . " for assistance.";
        }
        $this->isSuccessful = true;
    }

    private function sendWelcomeEmail(string $userEmail, string $newPass)
    {
        $storeTitle = $this->storeInfoService->storeInfo()['storeName'];
        $storeName = $this->storeInfoService->getStoreName();

        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
        $email->subject($storeTitle . " Password Reset Request");
        $email->to($userEmail);
        $email->htmlTemplate('emails/forgot_password.html.twig')->context([
            'password' => $newPass,
            'store_url' => StoreConfigEnum::getEnv('APP_USER_HOST'),
        ]);
        $this->mailer->send($email);
    }

    private function handleException(Exception $exception)
    {
        $this->flashError = true;
        $this->flashMessage = $exception->getMessage();
    }
}