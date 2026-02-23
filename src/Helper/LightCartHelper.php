<?php

namespace App\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Twig\LightCartProvider;
use App\Entity\Product;
use App\Enum\ProductEnum;

class LightCartHelper extends AbstractController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator, 
        private readonly EntityManagerInterface $entityManager,
        private readonly LightCartProvider $lightCartProvider
    )
    {
    }

    public function build(): array
    {
        $items = $this->lightCartProvider->getItems();
        $cartId = $this->lightCartProvider->getCartId();
        $totalQuantity = $this->lightCartProvider->getTotalQuantity();
        $totalAmount = $this->lightCartProvider->getTotalAmount();
        $cartTotalAmount = $this->lightCartProvider->getCartTotalAmount();

        $result = [
            'items' => (count($items) > 0) ? $items : [],
            'totalQuantity' => $totalQuantity,
            'totalAmount' => $totalAmount,
            'cartTotalAmount' => $cartTotalAmount,
            'cartId' => $cartId,
            'cart' =>  $this->generateUrl('cart', ['id' => $cartId], UrlGeneratorInterface::ABSOLUTE_URL),
            'checkout' =>  $this->generateUrl('checkout', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ];


        foreach ($result['items'] as $key => $item) {
            $result['items'][$key] = [
                ...$item,
                'editUrl' => $this->lightCartUrls($item, $cartId, type: 'edit'),
                'removeUrl' => $this->generateUrl('remove_item_from_cart',[], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $result;
    }

    private function lightCartUrls(array $item, ?string $cartId = null, ?string $type = null): string
    {
        if (!$type) {
            $type = 'edit';
        }
        if ($type === 'edit') {
            return $this->editCartUrl($item, $cartId);
        }else if ($type === 'cart') {
            return $this->generateUrl('cart', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        return $this->editCartUrl($item, $cartId);
    }

    public function editCartUrl(array|null $item, string|null $cartId): string|null
    {
        if(!$cartId || !$item ) return null;

        $params = [
            'category' => $item['categorySlug'],
            'productType' => $item['productTypeSlug'],
            'cartId' => $cartId,
            'itemId' => $item['id'],
            'sku' => $item['parentSku']
        ];

        if (isset($item['parentSku']) && $item['parentSku'] === ProductEnum::SAMPLE->value) {
            $sampleParams['itemId'] = $item['id'];
            $sampleParams['cartId'] = $cartId;
            $routeName = 'order_sample';
            return $this->urlGenerator->generate($routeName, $sampleParams, UrlGeneratorInterface::ABSOLUTE_URL);
        } else if (isset($item['parentSku']) && $item['parentSku'] === ProductEnum::WIRE_STAKE->value) {
            $wireParams['itemId'] = $item['id'];
            $wireParams['cartId'] = $cartId;
            $routeName = 'order_wire_stake';
            return $this->urlGenerator->generate($routeName, $wireParams, UrlGeneratorInterface::ABSOLUTE_URL);
        } else if (isset($item['parentSku']) && $item['parentSku'] === ProductEnum::BLANK_SIGN->value) {
            $blankSignParams['itemId'] = $item['id'];
            $blankSignParams['cartId'] = $cartId;
            $routeName = 'order_blank_sign';
            return $this->urlGenerator->generate($routeName, $blankSignParams, UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            if (isset($item['data']['customSize']['isSample']) && $item['data']['customSize']['isSample']) {
                $sampleParams['itemId'] = $item['id'];
                $sampleParams['cartId'] = $cartId;
                $routeName = 'order_sample';
                return $this->urlGenerator->generate($routeName, $sampleParams, UrlGeneratorInterface::ABSOLUTE_URL);
            } else if (isset($item['data']['customSize']['isCustomSize']) && $item['data']['customSize']['isCustomSize']) {
                $customSize = $item['data']['customSize'];
                $category = isset($customSize['category']) ? $customSize['category'] : $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $customSize['sku']])->getPrimaryCategory()->getSlug() ?? $item['categorySlug'];
                $params = array_merge($params, [
                    'category' => $category,
                    'sku' => $customSize['sku'],
                    'variant' => $customSize['templateSize']['width'] . 'x' . $customSize['templateSize']['height'],
                    'qty' => $item['quantity'],
                ]);
            }
            $routeName = 'editor';
        }

        $url = $this->urlGenerator->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_URL);

        return $url;
    }
}