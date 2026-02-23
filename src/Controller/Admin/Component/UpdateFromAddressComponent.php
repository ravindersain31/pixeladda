<?php

namespace App\Controller\Admin\Component;

use App\Constant\Editor\Addons;
use App\Entity\Order;
use App\Form\Admin\Order\UpdateFromAddressType;
use App\Service\EasyPost\EasyPost;
use App\Service\EasyPost\EasyPostAddress;
use App\Service\OrderLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "UpdateFromAddressComponent",
    template: "admin/components/order/update-from-address.html.twig"
)]
class UpdateFromAddressComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;


    #[LiveProp(writable: true)]
    public ?Order $order = null;

    /**
     * @var array
     * This is a return address will be printed at the label, don't change anything here
     */
    public array $defaultYSPAddress = ["id" => "adr_f61aab0691c711ef8d033cecef1b359e", "name" => "YARD SIGN PLUS", "company" => null, "street1" => "16107 KENSINGTON DR PMB 314", "street2" => null, "city" => "SUGAR LAND", "state" => "TX", "zip" => "77479-4224", "country" => "US", "phone" => "18779581499", "email" => "SALES@YARDSIGNPLUS.COM"];

    /**
     * @var array
     * This is a return address will be printed at the label, don't change anything here
     */
    public array $defaultBlindAddress = ["id" => "adr_3df83587b7ad11efb0e43cecef1b359e", "name" => '-', "company" => null, "street1" => "16107 KENSINGTON DR PMB 314", "street2" => "", "city" => "SUGAR LAND", "state" => "TX", "zip" => "77479-4224", "country" => "US", "phone" => "18779581499", "email" => null];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderLogger            $orderLogger,
        private readonly EasyPostAddress        $easyPostAddress,
        private readonly Addons                 $addons,
        private readonly ParameterBagInterface  $parameterBag,
    )
    {
        if ($this->parameterBag->get('EASYPOST_ENV') === 'test') {
            $this->defaultYSPAddress = ["id" => "adr_a88e4ffd9dc411ef941aac1f6bc539aa", "name" => null, "company" => "YARD SIGN PLUS", "street1" => "16107 KENSINGTON DR PMB 314", "street2" => "", "city" => "SUGAR LAND", "state" => "TX", "zip" => "77479-4224", "country" => "US", "phone" => "18779581499", "email" => null];
            $this->defaultBlindAddress = ["id" => "adr_6be60232b7ad11efa84eac1f6bc53342", "name" => '-', "company" => null, "street1" => "16107 KENSINGTON DR PMB 314", "street2" => "", "city" => "SUGAR LAND", "state" => "TX", "zip" => "77479-4224", "country" => "US", "phone" => "18779581499", "email" => null];
        }
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(UpdateFromAddressType::class, null, [
            'order' => $this->order,
        ]);
    }

    #[LiveAction]
    public function updateFromAddress(): ?Response
    {
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['addressType'] == 'custom') {
                $this->easyPostAddress->setCompany($data['companyName']);
                $this->easyPostAddress->setName($data['name']);
                $this->easyPostAddress->setStreet1($data['street1']);
                $this->easyPostAddress->setStreet2($data['street2']);
                $this->easyPostAddress->setCity($data['city']);
                $this->easyPostAddress->setState($data['state']);
                $this->easyPostAddress->setCountry($data['country']->getIsoCode());
                $this->easyPostAddress->setZip($data['zipcode']);
                $this->easyPostAddress->setPhone($data['phone']);

                $response = $this->easyPostAddress->create();
            } else {
                $addressId = $this->easyPostAddress->getReturnAddressId();
                if ($data['addressType'] == 'blind') {
                    $addressId = $this->easyPostAddress->getBlindAddressId();
                }
                $response = $this->easyPostAddress->get($addressId);
            }

            if ($response['success']) {
                $epAddress = $response['address'];
                $epAddress['updatedAt'] = (new \DateTimeImmutable())->getTimestamp();
                $this->order->setMetaDataKey('epFromAddressType', $data['addressType']);
                $this->order->setMetaDataKey('epFromAddress', $epAddress);
                $this->entityManager->persist($this->order);
                $this->entityManager->flush();

                $singleLineAddress = $epAddress['company'] . ' ' . $epAddress['name'] . ' ' . $epAddress['street1'] . ' ' . $epAddress['street2'] . ' ' . $epAddress['city'] . ' ' . $epAddress['state'] . ' ' . $epAddress['zip'] . ' ' . $epAddress['country'];
                $this->orderLogger->setOrder($this->order);
                $this->orderLogger->log('From address updated to: ' . $singleLineAddress);

                $this->addFlash('success', 'From address updated successfully.');
                return $this->redirect($this->generateUrl('admin_order_overview', ['orderId' => $this->order->getOrderId()]));
            }

            $this->addFlash('danger', $response['message']);
        }
        return null;
    }

    #[LiveAction]
    public function removeFromAddress(): Response
    {
        $this->order->setMetaDataKey('epFromAddress', null);
        $this->entityManager->persist($this->order);
        $this->entityManager->flush();

        $this->orderLogger->setOrder($this->order);
        $this->orderLogger->log('From address removed. So the order will use the default from address.');

        $this->addFlash('success', 'From address removed successfully.');
        return $this->redirect($this->generateUrl('admin_order_overview', ['orderId' => $this->order->getOrderId()]));
    }

}
