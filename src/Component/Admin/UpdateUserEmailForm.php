<?php

namespace App\Component\Admin;

use App\Entity\AppUser;
use App\Entity\Order;
use App\Form\Admin\Customer\UpdateEmailType;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Component\Validator\Constraints\NotNull;

#[AsLiveComponent(
    name: "UpdateUserEmailForm",
    template: "admin/customer/users/component/updateEmailForm.html.twig"
)]
class UpdateUserEmailForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UserService $userService){

    }

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;

    #[LiveProp(fieldName: 'formData')]
    public ?Order $order;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            UpdateEmailType::class,['order' => $this->order],
        );
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function save(): Response
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->isSuccessful = true;
        try {

            $user = $this->entityManager->getRepository(AppUser::class)->findOneBy(['email' => $data['email']]);

            if ($user) {
                $this->order->setUser($user);
                $this->entityManager->flush();
                $this->addFlash('success', 'Email updated successfully');
                return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
            }else{
                $newUser = $this->userService->createUserWithEmailAndAddress($data['email'], $this->order->getBillingAddress());
                if($newUser){
                    $this->order->setUser($newUser);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Email added successfully');
                } else {
                    throw new \Exception('User not found');
                }
                return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
            }

        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
        }
    }


}