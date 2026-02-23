<?php

namespace App\Controller\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderLog;
use App\Enum\OrderStatusEnum;
use App\Form\Admin\Order\ChangeOrderStatusType;
use App\Repository\OrderItemRepository;
use App\Repository\OrderLogRepository;
use App\Repository\OrderRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderPreviewController extends AbstractController
{
    #[Route('/orders/{orderId}/preview/{itemId}', name: 'order_item_preview')]
    public function orderView(string $orderId, string $itemId, OrderRepository $repository, OrderItemRepository $orderItemRepository): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $item = $orderItemRepository->findOneBy(['order' => $order, 'id' => $itemId]);

        return $this->render('admin/order/view/item_preview.html.twig', [
            'order' => $order,
            'item' => $item,
            'canvasData' => $item->getCanvasData(),
        ]);
    }

}
