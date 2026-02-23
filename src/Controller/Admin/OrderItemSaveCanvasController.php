<?php

namespace App\Controller\Admin;

use App\Repository\OrderItemRepository;
use App\Helper\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderItemSaveCanvasController extends AbstractController
{
    #[Route('/save-customer-canvas', name: 'save_customer_canvas', methods: ['POST'])]
    public function saveCanvas(
        Request $request,
        EntityManagerInterface $em,
        OrderItemRepository $repo,
        UploaderHelper $uploader
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['itemId'], $data['side'], $data['base64'])) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required fields: itemId, side, base64'
            ], 400);
        }

        $itemId = $data['itemId'];
        $side = $data['side'];
        $base64 = $data['base64'];
        $key = $data['type'] ?? 'customerDesign';

        $item = $repo->find($itemId);
        if (!$item) return $this->json(['success' => false, 'message' => 'Item not found'], 404);

        $png = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        $file = $uploader->createFileFromContents($png,  "design-$itemId-$side-$key-" . time() . ".png");
        $url = $uploader->upload($file, 'customDesignStorage');

        if (!$url) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to upload canvas image'
            ], 500);
        }
        $meta = $item->getMetaData() ?? [];
        $meta[$key] = $meta[$key] ?? [];
        $meta[$key][$side] = $url;
        $item->setMetaData($meta);

        $order = $item->getOrder();
        if ($order) {
            $order->setIsCanvasConverted(true);
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to save design to database'
            ], 500);
        }
        return $this->json(['success' => true, 'url' => $url]);
    }
}
