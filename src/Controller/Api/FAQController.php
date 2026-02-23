<?php

namespace App\Controller\Api;

use App\Constant\Faqs;
use App\Repository\CustomerPhotosRepository;
use App\Trait\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class FAQController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/faqs/list', name: 'list_faqs', methods: ['GET'])]
    public function customerPhotos(Faqs $faqs): Response
    {
        return $this->json($faqs->getFaqs());
    }

    #[Route('/warehouse-orders/test/mercure/', name: 'warehouse_orders_test_mercure', methods: ['GET'])]
    public function testMercure(Request $request, HubInterface $hub): JsonResponse
    {
        try{

            $update = new Update(
            'test',
            json_encode([
                'topic' => 'test',
                'data' => [
                    'message' => 'Hello from Mercure!',
                    ]
                ]),
                false
            );

            $res = $hub->publish($update);

            dump('Success');
            dump($update);
            dump($hub);
            dd($res);
        }catch(\Exception $e){
            dump('Error');
            dump($hub);
            dd($e);
        }
    }


}