<?php

namespace App\Twig;

use App\Constant\Editor\Addons;
use App\Entity\AdminUser;
use App\Entity\AppUser;
use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProofGrommetTemplate;
use App\Entity\ProofWireStakeTemplate;
use App\Entity\Reports\DailyCogsReport;
use App\Enum\OrderShipmentTypeEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\ProductEnum;
use App\Enum\ProductTypeEnum;
use App\Form\Admin\Order\ProofType;
use App\Form\Admin\Order\UpdateCheckPoPaymentType;
use App\Form\Admin\Order\UploadPrintCutFileType;
use App\Form\Admin\Order\UploadPrintFileType;
use App\Form\ReferralCouponType;
use App\Helper\PriceChartHelper;
use Detection\MobileDetect;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AppExtension extends AbstractExtension
{
    private User|null $user = null;

    public function __construct(
        private readonly FormFactoryInterface       $formFactory,
        private readonly EntityManagerInterface     $entityManager, 
        private readonly ParameterBagInterface      $parameterBagInterface, 
        private readonly TokenStorageInterface      $tokenStorage,
        private readonly SerializerInterface        $serializer, 
    ) 
    {
        $this->user = $this->tokenStorage->getToken()?->getUser();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('static', [$this, 'getStatic']),
            new TwigFunction('shipmentTypeEnum', [$this, 'shipmentTypeEnum']),
            new TwigFunction('getUserType', [$this, 'getUserType']),
            new TwigFunction('createFormView', [$this, 'createFormView']),
            new TwigFunction('createProofForm', [$this, 'createProofForm']),
            new TwigFunction('createPrintCutFileForm', [$this, 'createPrintCutFileForm']),
            new TwigFunction('labelForAddons', [$this, 'labelForAddons']),
            new TwigFunction('lowestPrice', [$this, 'lowestPrice']),
            new TwigFunction('reviewCount', [$this, 'reviewCount']),
            new TwigFunction('divide', [$this, 'divide']),
            new TwigFunction('updateCheckPoPaymentForm', [$this, 'updateCheckPoPaymentForm']),
            new TwigFunction('isMobile', [$this, 'isMobile']),
            new TwigFunction('getFontName', [$this, 'getFontName']),
            new TwigFunction('hasSubAddon', [$this, 'hasSubAddon']),
            new TwigFunction('getAdsPerOrder', [$this, 'getAdsPerOrder']),
            new TwigFunction('getShopperApprovedRating', [$this, 'getShopperApprovedRating']),
            new TwigFunction('getShopperApprovedReviews', [$this, 'getShopperApprovedReviews']),
            new TwigFunction('cartNeedsProof', [$this, 'cartNeedsProof']),
            new TwigFunction('getOrderCanvasData', [$this, 'getOrderCanvasData']),
            new TwigFunction('getGrommetTemplates', [$this, 'getGrommetTemplates']),
            new TwigFunction('getWireStakeTemplates', [$this, 'getWireStakeTemplates']),
            new TwigFunction('createReferralCoupoun', [$this, 'createReferralCoupoun'])

        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('addInchQuotes', [$this, 'addInchQuotes']),
            new TwigFilter('badgePaymentMethod', [$this, 'badgePaymentMethod']),
            new TwigFilter('badgePaymentStatus', [$this, 'badgePaymentStatus']),
            new TwigFilter('badgeOrderStatus', [$this, 'badgeOrderStatus']),
            new TwigFilter('lazyDefaultImage', [$this, 'lazyDefaultImage']),
            new TwigFilter('formatToE164', [$this, 'formatToE164']),
            new TwigFilter('base64_decode', [$this, 'base64Decode']),
            new TwigFilter('colorToCmyk', [$this, 'convertColorToCmyk']),
            new TwigFilter('getAddonsFrameDisplayText', [$this, 'getAddonsFrameDisplayText']),
            new TwigFilter('getFrameTypeDisplayText', [$this, 'getFrameTypeDisplayText']),
        ];
    }

    public function divide($val1, $val2): float|int
    {
        if ($val2 <= 0) {
            return 0;
        }
        return $val1 / $val2;
    }

    public function getStatic(string $class, string $property): ?string
    {
        if (property_exists($class, $property)) {
            return $class::$$property;
        }
        return null;
    }

    public function shipmentTypeEnum(string $type): OrderShipmentTypeEnum
    {
        return OrderShipmentTypeEnum::from($type);
    }

    public function getUserType($user): string
    {
        if ($user instanceof AppUser) {
            return 'Customer';
        } else if ($user instanceof AdminUser) {
            return 'Admin';
        }
        return 'Unknown';
    }

    public function createFormView(mixed $type, $data = null, array $options = []): FormView
    {
        return $this->formFactory->create($type, $data, $options)->createView();
    }

    public function createProofForm(string|null $action = null, $data = null, array $options = []): FormView
    {
        if ($action) {
            $options['action'] = $action;
        }
        return $this->formFactory->create(ProofType::class, $data, $options)->createView();
    }

    public function createReferralCoupoun($data = null, array $options = []): FormView
    {
        return $this->formFactory->create(ReferralCouponType::class, $data, $options)->createView();
    }

    public function createPrintCutFileForm(string|null $action = null, $data = null, array $options = []): FormView
    {
        if ($action) {
            $options['action'] = $action;
        }
        return $this->formFactory->create(UploadPrintCutFileType::class, $data, $options)->createView();
    }

    public function updateCheckPoPaymentForm(string|null $action = null, $data = null, array $options = []): FormView
    {
        if ($action) {
            $options['action'] = $action;
        }
        return $this->formFactory->create(UpdateCheckPoPaymentType::class, $data, $options)->createView();
    }

    public function addInchQuotes(string $string): string
    {
        $arr = explode('x', $string);
        if (count($arr) === 2) {
            return $arr[0] . '" x ' . $arr[1] . '"';
        }
        return $string;
    }

    public function labelForAddons(array $addons): array
    {
        $sortAdons = [
            1 => 'sides',
            2 => 'shape',
            3 => 'imprintColor',
            4 => 'grommets',
            5 => 'grommetColor',
            6 => 'frame',
            7 => 'flute',
        ];
        uksort($addons, function ($a, $b) use ($sortAdons) {
            $posA = array_search($a, $sortAdons);
            $posB = array_search($b, $sortAdons);
            return $posA <=> $posB;
        });
        $labels = [];
        foreach ($addons as $key => $addon) {
            if (Addons::hasSubAddon($addon)) {
                foreach ($addon as $subAddonKey => $subAddonValue) {
                    if ($subAddonValue['key'] === 'NONE') {
                        continue;
                    }
                    if ($key === 'grommetColor' && $subAddonValue['key'] === 'NONE') {
                        continue;
                    }
                    if ($subAddonValue['displayText']) {
                        $labels[] = $subAddonValue['displayText'];
                    } else {
                        $label = ucfirst(strtolower(str_replace('_', ' ', $subAddonValue['key'])));
                        $addonLabel = match ($subAddonKey) {
                            'frame' => 'Frame',
                            'sides' => 'Sides - ',
                            'grommets' => 'Grommets - ',
                            'grommetColor' => 'Grommet Color - ',
                            'imprintColor' => 'Imprint Color - ',
                            default => $subAddonValue['label'],
                        };
                        $labels[] = $addonLabel . ' ' . $label;
                    }
                }
            } else {
                if ($addon['key'] === 'NONE') {
                    continue;
                }
                if ($key === 'grommetColor' && $addons['grommets']['key'] === 'NONE') {
                    continue;
                }
                if ($addon['displayText']) {
                    $labels[] = $addon['displayText'];
                } else {
                    $label = ucfirst(strtolower(str_replace('_', ' ', $addon['key'])));
                    $addonLabel = match ($key) {
                        'frame' => ' Frame',
                        'sides' => 'Sides - ',
                        'grommets' => 'Grommets - ',
                        'grommetColor' => 'Grommet Color - ',
                        'imprintColor' => 'Imprint Color - ',
                        default => $addon['label'],
                    };
                    $labels[] = $addonLabel . ' ' . $label;
                }
            }
        }
        return $labels;
    }

    public function badgePaymentStatus($status, bool $onlyLabel = false): string
    {
        $label = PaymentStatusEnum::LABELS[$status] ?? $status;
        if ($onlyLabel) {
            return $label;
        }
        return match ($status) {
            PaymentStatusEnum::INITIATED => '<span class="badge bg-warning text-white">' . $label . '</span>',
            PaymentStatusEnum::PARTIALLY_REFUNDED => '<span class="badge badge bg-light text-dark bg-light-dark">' . $label . '</span>',
            PaymentStatusEnum::VOIDED => '<span class="badge bg-light text-dark bg-light-dark">' . $label . '</span>',
            PaymentStatusEnum::UNKNOWN => '<span class="badge bg-light text-dark bg-light-dark">' . $label . '</span>',
            PaymentStatusEnum::PENDING => '<span class="badge bg-warning">' . $label . '</span>',
            PaymentStatusEnum::COMPLETED => '<span class="badge bg-success">' . $label . '</span>',
            PaymentStatusEnum::PROCESSING => '<span class="badge bg-warning">' . $label . '</span>',
            OrderStatusEnum::REFUNDED => '<span class="badge bg-light text-dark badge-orange-soft">' . $label . '</span>',
            default => '<span class="badge bg-light text-dark">' . $label . '</span>'
        };
    }

    public function badgePaymentMethod($status, bool $onlyLabel = true): string
    {
        $label = PaymentMethodEnum::LABELS[$status] ?? $status;
        if ($onlyLabel) {
            return $label;
        }

        return '<span class="badge bg-light text-dark">' . $label . '</span>';
    }


    public function badgeOrderStatus($status, bool $small = false, bool $customer = false): string
    {
        $label = OrderStatusEnum::LABELS[$status] ?? $status;
        if ($customer) {
            $label = OrderStatusEnum::CUSTOMER_LABELS[$status] ?? $label;
        }
        if ($small && $status === OrderStatusEnum::READY_FOR_SHIPMENT) {
            $label = 'Entered SE';
        }
        return match ($status) {
            OrderStatusEnum::CREATED => '<span class="badge bg-warning badge-pink-soft">' . $label . '</span>',
            OrderStatusEnum::CHANGES_REQUESTED => '<span class="badge bg-warning">' . $label . '</span>',
            OrderStatusEnum::DESIGNER_ASSIGNED => '<span class="badge bg-info badge-blue-soft">' . $label . '</span>',
            OrderStatusEnum::RECEIVED => '<span class="badge bg-dark badge-cyan-soft text-white">' . $label . '</span>',
            OrderStatusEnum::PROOF_APPROVED => '<span class="badge bg-success badge-blue-soft">' . $label . '</span>',
            OrderStatusEnum::PROOF_UPLOADED => '<span class="badge bg-success">' . $label . '</span>',
            OrderStatusEnum::SHIPPED => '<span class="badge text-white bg-dark badge-yellow-soft">' . $label . '</span>',
            OrderStatusEnum::CANCELLED => '<span class="badge bg-light text-dark badge-danger-soft">' . $label . '</span>',
            default => '<span class="badge bg-light text-dark">' . $label . '</span>'
        };
    }

    public function lowestPrice($product, ?string $variant = null, string $currency = 'USD'): float
    {
        $user = $this->user;
        $pricing = $product['productPricing'];
        $productType = $product['productType'];
        if (count($pricing) <= 0) {
            $pricing = $product['productTypePricing'];
        }
        $pricing = PriceChartHelper::getHostBasedPrice($pricing, $productType,$user);
        return PriceChartHelper::getLowestPrice($pricing, $currency, $variant);
    }

    public function reviewCount(): string
    {
        return (string) rand(30, 150);
    }

    public function getShopperApprovedData(): ?array
    {
        $apiKey = "11bef1d5a6";
        $storeId = "36686";
        $url = "https://api.shopperapproved.com/aggregates/reviews/{$storeId}?token={$apiKey}&xml=false";

        try {
            $options = [
                "http" => [
                    "header" => "Authorization: Bearer {$apiKey}\r\n" .
                        "Content-Type: application/json\r\n"
                ]
            ];
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);

            if (!isset($data['error_code'])) {
                return [
                    'average_rating' => isset($data['average_rating']) ? (float) $data['average_rating'] : null,
                    'total_reviews' => isset($data['total_reviews']) ? (int) $data['total_reviews'] : null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getShopperApprovedRating(): string
    {
        $shopperApprovedData = $this->getShopperApprovedData();

        return (string) $shopperApprovedData['average_rating'] ?? '0';
    }

    public function getShopperApprovedReviews(): string
    {
        $shopperApprovedData = $this->getShopperApprovedData();

        return (string) $shopperApprovedData['total_reviews'] ?? '0';
    }

    public function lazyDefaultImage(string|null $url): string
    {
        if (!$url) {
            return '/app-images/images/blank.gif';
        }
        return str_replace('static.yardsignplus.com/', 'static.yardsignplus.com/filters:blur(30)/fit-in/200x200/', $url);
    }

    public function formatToE164(string $phoneNumber): string
    {
        $defaultCountryCode = '+1';

        // Remove all non-numeric characters from the phone number, except for the leading '+'
        $phoneNumber = preg_replace('/[^+\d]/', '', $phoneNumber);

        // If the phone number starts with '0', remove it
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // If the phone number doesn't start with a '+' sign, prepend the default country code
        if (!str_starts_with($phoneNumber, '+')) {
            // If the default country code is present in the phone number, remove it
            if (str_starts_with($phoneNumber, $defaultCountryCode)) {
                $phoneNumber = substr($phoneNumber, strlen($defaultCountryCode));
            }
            $phoneNumber = $defaultCountryCode . $phoneNumber;
        }

        return $phoneNumber;
    }

    public function isMobile(): bool
    {
        $mobileDetect = new MobileDetect();
        try {
            return $mobileDetect->isMobile();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function base64Decode($value): string
    {
        return $value ? base64_decode($value) : '';
    }

    public function convertColorToCmyk(string $color): array
    {
        // Check if the color is HEX (starts with `#`)
        if (strpos($color, '#') === 0) {
            return $this->convertHexToCmyk($color);
        }

        // Check if the color is RGB (starts with `rgb(`)
        if (strpos($color, 'rgb(') === 0) {
            return $this->convertRgbStringToCmyk($color);
        }

        return [];
    }

    private function convertHexToCmyk(string $hexColor): array
    {
        $hexColor = ltrim($hexColor, '#');

        $r = hexdec(substr($hexColor, 0, 2)) / 255;
        $g = hexdec(substr($hexColor, 2, 2)) / 255;
        $b = hexdec(substr($hexColor, 4, 2)) / 255;

        return $this->convertRgbToCmyk($r, $g, $b);
    }

    private function convertRgbStringToCmyk(string $rgbColor): array
    {
        // Extract RGB values from the string (e.g., rgb(0, 0, 0))
        preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $rgbColor, $matches);

        if (count($matches) !== 4) {
            return [];
        }

        $r = $matches[1] / 255;
        $g = $matches[2] / 255;
        $b = $matches[3] / 255;

        return $this->convertRgbToCmyk($r, $g, $b);
    }

    private function convertRgbToCmyk(float $r, float $g, float $b): array
    {
        $k = 1 - max($r, $g, $b);
        $c = ($k < 1) ? (1 - $r - $k) / (1 - $k) : 0;
        $m = ($k < 1) ? (1 - $g - $k) / (1 - $k) : 0;
        $y = ($k < 1) ? (1 - $b - $k) / (1 - $k) : 0;

        return [
            'c' => round($c * 100),
            'm' => round($m * 100),
            'y' => round($y * 100),
            'k' => round($k * 100),
        ];
    }

    public function getFontName(string $fontFamily): ?string
    {
        $fontsFilePath = $this->parameterBagInterface->get('kernel.project_dir') . '/assets/react/editor/fonts.json';

        if (!file_exists($fontsFilePath)) {
            return null;
        }

        $fonts = json_decode(file_get_contents($fontsFilePath), true);
        foreach ($fonts as $font) {
            if (in_array($fontFamily, $font['family'], true)) {
                return $font['name'];
            }
        }

        return null;
    }

    public function hasSubAddon($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }

    public function getAddonsFrameDisplayText(string $key): ?string
    {
        return htmlentities(Addons::getFrameQuantityType($key));
    }

    public function getFrameTypeDisplayText(string $key): ?string
    {
        return Addons::getFrameDisplayText($key);
    }

    public function getAdsPerOrder(Order $order): mixed
    {
        $dailyReport = $this->entityManager->getRepository(DailyCogsReport::class)->findOneBy(['date' => $order->getOrderAt()]);

        if (!$dailyReport instanceof DailyCogsReport) {
            return 0; // Return 0 if no report is found
        }

        $totalAdsCost = $dailyReport->getTotalAdsCost() ?? 0;
        $totalPaidSales = $dailyReport->getTotalPaidSales() ?? 0;
        $orderTotal = $order->getTotalAmount() ?? 0;

        return $totalPaidSales <= 0 ? 0 : floatval($this->divide($orderTotal, $totalPaidSales) * $totalAdsCost);
    }

    public function cartNeedsProof(?Cart $cart): bool
    {
        if (!$cart) {
            return false;
        }

        if ($cart->isSample() || $cart->isWireStake() || $cart->isWireStakeAndSampleAndBlankSign() || $cart->isBlankSign()) {
            return true;
        }

        foreach ($cart->getCartItems() as $item) {
            $artwork = $item->getDataKey('customOriginalArtwork') ?? [];

            if($item->getDataKey('isEmailArtworkLater') || $item->getDataKey('isHelpWithArtwork')) {
                return true;
            }

            $isCustomProduct = $item->getProduct()->getParent()->getSku() === ProductEnum::CUSTOM->value;
            $isYardLetterProduct = $item->getProduct()->getParent()->getProductType()->getSlug() === ProductTypeEnum::YARD_LETTERS->value;

            if ($isYardLetterProduct) {
                return true;
            }

            if ($isCustomProduct && empty($artwork)) {
                return true;
            }

            foreach ($artwork as $sideFiles) {
                if (!is_array($sideFiles)) {
                    continue;
                }

                foreach ($sideFiles as $file) {
                    $url = $file['url'] ?? $file['originalFileUrl'] ?? null;
                    if (!$url) {
                        continue;
                    }

                    if (
                        str_ends_with($url, '.csv') ||
                        str_ends_with($url, '.xls') ||
                        str_ends_with($url, '.xlsx')
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getOrderCanvasData(Order $order): array
    {
        $json = $this->serializer->serialize(
            $order->getOrderItems(),
            'json',
            ['groups' => 'apiCanvasData']
        );

        return json_decode($json, true);
    }

    public function getGrommetTemplates(): array
    {
        $grommetTemplates = [];
        /** @var ProofGrommetTemplate[] $grommetEntities */
        $grommetEntities = $this->entityManager->getRepository(ProofGrommetTemplate::class)->findAll();
        foreach ($grommetEntities as $entity) {
            $grommetTemplates[strtoupper($entity->getGrommetColor())] = $entity->getImageUrl();
        }
        return $grommetTemplates;
    }

    public function getWireStakeTemplates(): array
    {
        $wireStakeTemplates = [];
        /** @var ProofWireStakeTemplate[] $wireStakeEntities */
        $wireStakeEntities = $this->entityManager->getRepository(ProofWireStakeTemplate::class)->findAll();
        foreach ($wireStakeEntities as $entity) {
            $wireStakeTemplates[strtoupper($entity->getWireStakeType())] = $entity->getImageUrl();
        }
        return $wireStakeTemplates;
    }

    /**
     * Convert a URL to a base64 data URI
     */
    public function urlToBase64(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'ignore_errors' => false
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $imageData = @file_get_contents($url, false, $context);
            
            if ($imageData !== false) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageData) ?: 'image/jpeg';

                if (!str_starts_with($mimeType, 'image/')) {
                    return $url;
                }

                $base64 = base64_encode($imageData);
                return "data:$mimeType;base64,$base64";
            }
        } catch (\Exception $e) {
            // Fallback to original URL
        }

        return $url;
    }
}
