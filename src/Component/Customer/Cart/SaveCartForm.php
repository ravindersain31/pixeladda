<?php

namespace App\Component\Customer\Cart;

use App\Entity\Cart;
use App\Entity\SavedCart;
use App\Enum\KlaviyoEvent;
use App\Enum\StoreConfigEnum;
use App\Repository\SavedCartRepository;
use App\Repository\StoreDomainRepository;
use App\Service\CartManagerService;
use App\Service\KlaviyoService;
use App\Service\StoreInfoService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "SaveCartForm",
    template: "components/cart/_save_cart.html.twig"
)]
class SaveCartForm
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService            $userService,
        private readonly MailerInterface        $mailer,
        private readonly SavedCartRepository    $savedCartRepository,
        private readonly CartManagerService     $cartManagerService,
        private readonly KlaviyoService         $klaviyoService,
        private readonly StoreInfoService       $storeInfoService,
        private readonly StoreDomainRepository  $storeDomainRepository,
    )
    {
    }

    #[LiveProp(writable: true)]
    #[NotBlank(message: 'This field is required')]
    #[Email(message: 'The email must be valid email address')]
    public string $email = '';

    #[LiveProp]
    #[NotNull]
    public ?string $cartId;

    #[LiveProp]
    public array $liveProp = [];
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;


    public function getLiveProp(): array
    {
        return $this->liveProp = ['cartId' => $this->cartId];
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validate();
        $this->isSuccessful = true;

        try {
            $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['cartId' => $this->cartId]);
            // $newCart = $this->cartManagerService->deepClone($cart, $this->itemId);
            $newCart = $this->cartManagerService->deepClone($cart); // Save Entire Cart

            $savedCart = new SavedCart();
            $savedCart->setCart($newCart);

            $data['email'] = $this->email;
            $user = $this->userService->getUserFromAddress($data);
            $savedCart->setUser($user);

            $host = $this->storeInfoService->storeInfo()['storeHost'];
            $storeDomain = $this->storeDomainRepository->findByDomain($host);
            $savedCart->setStoreDomain($storeDomain);

            $this->savedCartRepository->save($savedCart, true);
            $this->flashMessage = 'Your cart has been successfully saved.';
            $this->sendEmail($newCart, $this->email);
            $this->klaviyoService->saveCartDesignQuote($newCart, KlaviyoEvent::SAVE_CART, $this->email);

            $this->dispatchBrowserEvent('modal:close');
            $this->email = '';
            $this->resetValidation();

        } catch (\Exception $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashError = 'danger';
        }
    }

    private function sendEmail(Cart $cart, $email): void
    {
        $storeName = $this->storeInfoService->getStoreName();
        $message = (new TemplatedEmail());
        $message->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $message->subject("Your Shopping Cart Has Been Saved");
        $message->to(new Address($email));
        $message->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));

        $message->htmlTemplate('emails/cart_save.html.twig')->context([
            'cart' => $cart,
        ]);

        $this->mailer->send($message);
    }

    #[LiveAction]
    public function resetForm(): void
    {
        $this->email = '';
        $this->resetValidation();
        $this->isSuccessful = false;
        $this->flashMessage = null;
        $this->flashError = 'success';
    }
}