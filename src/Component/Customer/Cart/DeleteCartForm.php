<?php

namespace App\Component\Customer\Cart;

use App\Repository\CartRepository;
use App\Repository\SavedCartRepository;
use App\Service\CartManagerService;
use App\Service\SaveCartService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "DeleteCartForm",
    template: "components/cart/_delete_cart.html.twig"
)]
class DeleteCartForm extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManagerService $cartManagerService,
    ) {
    }

    #[LiveProp]
    #[NotNull]
    public $cartId;
    #[LiveProp]
    #[NotNull]
    public $itemId;

    #[LiveProp]
    public array $liveProp = [];
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;


    public function getLiveProp()
    {
        return $this->liveProp = ['itemId' => $this->itemId, 'cartId' => $this->cartId];
    }

    #[LiveAction]
    public function delete(Request $request)
    {
        $this->validate();
        $this->isSuccessful = true;

        try {
            $cart = $this->cartManagerService->getCart($this->cartId);
            $cart->setInternationalShippingCharge(false);
            $cart->setInternationalShippingChargeAmount(0);
            $cart = $this->cartManagerService->removeItem($this->cartId, $this->itemId);
            $referer = $request->headers->get('referer');
            $this->addFlash('success', 'Items removed successfully.');
            if ($referer) {
                return $this->redirect($referer);
            }

            return $this->redirectToRoute('cart', ['id' => $cart->getCartId()]);

        } catch (Exception $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashError = 'danger';
            return false;
        }
    }
}