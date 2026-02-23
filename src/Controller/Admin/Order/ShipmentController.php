<?php

namespace App\Controller\Admin\Order;

use App\Entity\AdminFile;
use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderShipment;
use App\Entity\User;
use App\Entity\UserFile;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Event\OrderProofUploadedEvent;
use App\Form\Admin\Order\ProofType;
use App\Form\Admin\Order\UploadPrintCutFileType;
use App\Helper\VichS3Helper;
use App\Repository\OrderRepository;
use App\Service\OrderLogger;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ShipmentController extends AbstractController
{

    use StoreTrait;

    #[Route('/orders/{orderId}/proofs/shipment', name: 'order_create_shipment')]
    public function transactions(string $orderId, OrderRepository $repository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $messages = $entityManager->getRepository(OrderMessage::class)->getProofMessages($order);


        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
            'messages' => $messages,
        ]);
    }
}
