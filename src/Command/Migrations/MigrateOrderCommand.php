<?php

namespace App\Command\Migrations;

use App\Entity\Admin\Coupon;
use App\Entity\Order;
use App\Entity\Product;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Helper\ProductConfigHelper;
use App\Service\CartManagerService;
use App\Service\OrderService;
use App\Service\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'migrations:migrate:order',
    description: 'Add a short description for your command',
)]
class MigrateOrderCommand extends Command
{
    private array $store = [
        "id" => 1,
        "name" => "YSP",
        "shortName" => "YSP",
        "domainId" => 1,
        "domainName" => "USA",
        "domain" => "local.yardsignplus.com",
        "currencyId" => 1,
        "currencyName" => "US Dollar",
        "currencySymbol" => "$",
        "currencyCode" => "USD",
    ];

    private array $paymentMethodConfig = [
        'creditcard' => PaymentMethodEnum::CREDIT_CARD,
        'paypal' => PaymentMethodEnum::PAYPAL,
        'paylater' => PaymentMethodEnum::SEE_DESIGN_PAY_LATER,
        'cheque' => PaymentMethodEnum::CHECK,
        'googlepay' => PaymentMethodEnum::GOOGLE_PAY,
    ];

    private array $orderStatusConfig = [
        'Approved Proofs' => OrderStatusEnum::PROOF_APPROVED,
        'Archived Orders' => OrderStatusEnum::ARCHIVE,
        'Cancelled Orders' => OrderStatusEnum::CANCELLED,
        'Completed' => OrderStatusEnum::COMPLETED,
        'Customer Change Request' => OrderStatusEnum::CHANGES_REQUESTED,
        'Designer Assigned' => OrderStatusEnum::DESIGNER_ASSIGNED,
        'Entered into Shippingeasy' => OrderStatusEnum::SENT_FOR_PRODUCTION,
        'In Production' => OrderStatusEnum::SENT_FOR_PRODUCTION,
        'Order Created' => OrderStatusEnum::CREATED,
        'Partially Shipped' => OrderStatusEnum::SHIPPED,
        'Proccessing (Under Payment Gateway)' => OrderStatusEnum::PROCESSING,
        'Proof Approved' => OrderStatusEnum::PROOF_APPROVED,
        'Proof Sent' => OrderStatusEnum::PROOF_UPLOADED,
        'Received' => OrderStatusEnum::RECEIVED,
        'Refunded' => OrderStatusEnum::REFUNDED,
        'Shipped' => OrderStatusEnum::SHIPPED,
        'Paid' => OrderStatusEnum::RECEIVED,
        'Upload Proofs' => OrderStatusEnum::RECEIVED,
        'Waiting on Customer' => OrderStatusEnum::PROOF_UPLOADED,
        'Working on proofs' => OrderStatusEnum::DESIGNER_ASSIGNED,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManagerService     $cartManagerService,
        private readonly OrderService           $orderService,
        private readonly ProductConfigHelper    $productConfigHelper,
        private readonly UserService            $userService
    )
    {
        parent::__construct();
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $query = "SELECT * FROM sm3_order AS O WHERE O.store_id = 2";
//        $query = "SELECT * FROM sm3_order AS O WHERE O.store_id = 2 and order_no=8800428839";
        $results = $this->sourceConnection->fetchAllAssociative($query);

        $orders = $this->fetchOrders($results);

        if (!empty($orders)) {
            $io->note(sprintf('Total Orders: %s', count($orders)));
            foreach ($orders as $orderId => $order) {
                $io->info(sprintf($order['key'] . ' Migrating Order Id #%s of Email: %s', $orderId, $order['customers']['email']));
                if ($this->entityManager->getRepository(Order::class)->findOneBy(["orderId" => $orderId])) {
                    $io->comment(sprintf('Order ID Already Exist : %s', $orderId));
                } else {
                    $this->createNewOrder($order, $orderId, $io);
                }
            }
            $this->entityManager->flush();
        } else {
            $io->warning('No Orders found.');
        }

        $io->success('Migration completed.');

        return Command::SUCCESS;
    }

    private function createNewOrder($order, $orderId, $io): void
    {
        $totals = $this->getTotals($order);
        $editorData = $this->buildEditorData($order, $io);
        $cart = $this->cartManagerService->createCart();
        $cart->setVersion('V1');
        if ($totals['coupon'] > 0) {
            $cart->setCoupon($this->entityManager->getReference(Coupon::class, 1));
            $cart->setCouponAmount($totals['coupon']);
        }
        if ($totals['shipping'] > 0) {
            $cart->setTotalShipping($totals['shipping']);
        }

        if ($totals['orderProtection'] > 0) {
            $cart->setOrderProtection(true);
            $cart->setOrderProtectionAmount($totals['orderProtection']);
        }

        $cart = $this->cartManagerService->updateCart($cart, $editorData);
        if ($cart->getCartItems()->count() > 0) {
            $io->comment(sprintf('Creating Order #%s', $orderId));
            $newOrder = $this->orderService->startOrder($cart, $this->store);
            $newOrder->setVersion('V1');
            $newOrder->setOrderId($orderId);
            $newOrder->setMetaDataKey('migratedData', $order['order']);
            $newOrder->setCompanyShippingCost(floatval($order['order']['admin_shipping']));
            $newOrder->setTextUpdatesNumber($order['order']['sms_update_mobile']);
            $newOrder->setDeliveryDate(new \DateTimeImmutable($order['order']['earliest_delivery_date']));
            $newOrder->setOrderAt(new \DateTimeImmutable($order['order']['created_at']));

            $shippingAddress = $this->makeShippingAddress($order);
            $newOrder->setShippingAddress($shippingAddress);
            $billingAddress = $this->makeBillingAddress($order);
            $newOrder->setBillingAddress($billingAddress);

            $orderingUser = $this->userService->getUserFromAddress($billingAddress);
            $newOrder->setUser($orderingUser);


            $paymentMethod = $this->paymentMethodConfig[$order['order']['payment_code']];
            $this->orderService->setItems($cart->getCartItems());
            $this->orderService->setPaymentMethod($paymentMethod);

            $newOrder->setAgreeTerms(true);

            if (isset($order['order_status']['name'])) {
                $newOrder->setStatus($this->orderStatusConfig[$order['order_status']['name']]);
            } else {
                $newOrder->setStatus(OrderStatusEnum::CREATED);
            }
            $newOrder->setPaymentStatus(PaymentStatusEnum::COMPLETED);

            if ($totals['refunded'] > 0) {
                $newOrder->setRefundedAmount($totals['refunded']);
            }

            $this->orderService->endOrder();
            $newOrder->setTotalReceivedAmount($newOrder->getTotalAmount());

            $this->entityManager->persist($newOrder);
            $this->entityManager->flush();
        } else {
            $io->note(sprintf('No items found with Order : Cart Id : %s', $cart->getCartId()));
        }
    }

    private function getTotals($order): array
    {
        $totals = [
            'coupon' => 0,
            'shipping' => 0,
            'total' => 0,
            'refunded' => 0,
            'orderProtection' => 0,
        ];
        foreach ($order['order_total'] as $total) {
            if ($total['code'] === 'coupon') {
                $totals['coupon'] = abs(floatval($total['value']));
            } else if ($total['code'] === 'shipping') {
                $totals['shipping'] = abs(floatval($total['value']));
            } else if ($total['code'] === 'sub_total') {
                $totals['subTotal'] = abs(floatval($total['value']));
            } else if ($total['code'] === 'total') {
                $totals['total'] = abs(floatval($total['value']));
            } else if ($total['code'] === 'refunded') {
                $totals['refunded'] = abs(floatval($total['value']));
            } else if ($total['code'] === 'order_protection') {
                $totals['orderProtection'] = abs(floatval($total['value']));
            }
        }
        return $totals;
    }


    private function buildEditorData($order, $io): array
    {
        $items = $this->buildEditorItems($order, $io);
        return [
            "subTotalAmount" => 0,
            "totalAmount" => 0,
            "totalShipping" => 0,
            "totalQuantity" => 0,
            "items" => $items,
            "shipping" => isset($items[0]) ? $items[0]['shipping'] : [
                'day' => 6,
                'date' => $order['order']['earliest_delivery_date'],
                'amount' => 0,
            ]
        ];
    }


    private function buildEditorItems($order, $io): array
    {
        $items = [];
        $canvasData = $this->parseCanvasData($order);
        foreach ($order['items'] as $item) {
            $templateId = $item['order_template_id'];
            $templateV1 = $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_template_designs WHERE id=$templateId");
            if (!$templateV1) {
                continue;
            }
            $sku = $templateV1['sku'];
            if (in_array($sku, ['CM00001'])) {
                $sku = 'CUSTOM';
            }

            $product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
            if (!$product instanceof Product) {
                $io->note(sprintf('Product not found in new version for SKU : %s', $sku));
                continue;
            }

            $itemId = $item['order_product_id'];
            $parsedOptions = $this->parseOptions($order, $item);
            if (isset($parsedOptions[$itemId])) {
                $options = $parsedOptions[$itemId];
                $variant = $this->entityManager->getRepository(Product::class)->findOneBy(['name' => $options['size'], 'parent' => $product]);

                $variantData = $this->productConfigHelper->getProductVariant($variant, []);
                $unitAmount = floatval($item['price']);
                $unitAddOnsAmount = $options['addonsUnitAmount'];
                $price = floatval($options['price']);
                $items[$variant->getId()] = [
                    ...$variantData,
                    "quantity" => intval($item['qty']),
                    "addons" => $options['addons'],
                    "price" => $price,
                    "unitAmount" => $unitAmount,
                    "unitAddOnsAmount" => $unitAddOnsAmount,
                    "totalAmount" => intval($item['qty']) * $unitAmount,
                    "canvasData" => $variantData['isCustom'] ? [
                        'front' => $options['files'] ?? [],
                        'back' => [],
                    ] : $canvasData[$item['order_template_id']] ?? [
                        'front' => [],
                        'back' => [],
                    ],
                    "additionalNote" => $options['additionalComments'] ?? '',
                    "shipping" => [
                        "day" => ($item['delivery_date'] - 1) <= 0 ? 1 : $item['delivery_date'] - 1,
                        "date" => $options['deliveryDate'],
                        "amount" => floatval($options['deliveryDatePrice']),
                    ]
                ];
            }
        }
        return $items;
    }

    private function makeShippingAddress($order): array
    {
        $country = $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_countries WHERE id = '" . $order['address']['shipping_country_id'] . "'");
        $zone = $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_zones WHERE id = '" . $order['address']['shipping_zone_id'] . "'");
        return [
            'firstName' => $order['address']['shipping_first_name'],
            'lastName' => $order['address']['shipping_last_name'],
            'addressLine1' => $order['address']['shipping_address_1'],
            'addressLine2' => $order['address']['shipping_address_2'],
            'city' => $order['address']['shipping_city'],
            'state' => $zone['code'] ?? '',
            'zipcode' => $order['address']['shipping_postcode'],
            'country' => $country['iso_code_2'] ?? '',
            'email' => $order['address']['shipping_email'],
            'phone' => $order['address']['shipping_phone'],
        ];
    }

    private function makeBillingAddress($order): array
    {
        $country = $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_countries WHERE id = '" . $order['address']['shipping_country_id'] . "'");
        $zone = $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_zones WHERE id = '" . $order['address']['shipping_zone_id'] . "'");
        return [
            'firstName' => $order['address']['billing_first_name'],
            'lastName' => $order['address']['billing_last_name'],
            'addressLine1' => $order['address']['billing_address_1'],
            'addressLine2' => $order['address']['billing_address_2'],
            'city' => $order['address']['billing_city'],
            'state' => $zone['code'] ?? '',
            'zipcode' => $order['address']['billing_postcode'],
            'country' => $country['iso_code_2'] ?? '',
            'email' => $order['address']['billing_email'],
            'phone' => $order['address']['billing_phone'],
        ];
    }

    private function parseCanvasData($order): array
    {
        $canvasData = [];
        foreach ($order['order_canvas_designs'] as $design) {
            $canvasData[$design['template_parent_id']] = [
                'front' => json_decode($design['front_json'], true) ?? null,
                'back' => json_decode($design['back_json'], true) ?? null,
            ];
        }
        return $canvasData;
    }

    private function parseOptions($order, $item): array
    {
        $addonsConfig = [
            "key" => "NONE",
            "type" => "PERCENTAGE",
            "label" => "Choose Your Frame (None)",
            "amount" => 0,
            "unitAmount" => 0.0,
            "displayText" => "No Frame"
        ];
        $addonsList = [
            "Choose Your Sides" => [
                "configKey" => "sides",
                "values" => [
                    "Single Sided" => [
                        "key" => "SINGLE",
                        "amount" => 0,
                    ],
                    "Single Side" => [
                        "key" => "SINGLE",
                        "amount" => 0,
                    ],
                    "Double Sided" => [
                        "key" => "DOUBLE",
                        "amount" => 40,
                    ],
                    "Both Sided" => [
                        "key" => "DOUBLE",
                        "amount" => 40,
                    ],
                ]
            ],
            "Choose Imprint Colors" => [
                "configKey" => "imprintColor",
                "values" => [
                    "1 Imprint Color" => [
                        "key" => "ONE",
                        "amount" => 0,
                    ],
                    "2 Imprint Colors" => [
                        "key" => "TWO",
                        "amount" => 20,
                    ],
                    "2 Imprint Color" => [
                        "key" => "TWO",
                        "amount" => 20,
                    ],
                    "3 Imprint Colors" => [
                        "key" => "THREE",
                        "amount" => 30,
                    ],
                    "3 Imprint Color" => [
                        "key" => "THREE",
                        "amount" => 30,
                    ],
                    "Unlimited Imprint Colors" => [
                        "key" => "UNLIMITED",
                        "amount" => 40,
                    ],
                    "Unlimited Imprint Color" => [
                        "key" => "UNLIMITED",
                        "amount" => 40,
                    ],
                ]
            ],
            "Imprint Colors" => [
                "configKey" => "imprintColor",
                "values" => [
                    "1 Imprint Color" => [
                        "key" => "ONE",
                        "amount" => 0,
                    ],
                    "2 Imprint Colors" => [
                        "key" => "TWO",
                        "amount" => 20,
                    ],
                    "3 Imprint Colors" => [
                        "key" => "THREE",
                        "amount" => 30,
                    ],
                    "3 Imprint Color" => [
                        "key" => "THREE",
                        "amount" => 30,
                    ],
                    "Unlimited Imprint Colors" => [
                        "key" => "UNLIMITED",
                        "amount" => 40,
                    ],
                ]
            ],
            "Choose Your Grommets" => [
                "configKey" => "grommets",
                "values" => [
                    "None" => [
                        "key" => "NONE",
                        "amount" => 0,
                    ],
                    "Top Center" => [
                        "key" => "TOP_CENTER",
                        "amount" => 15,
                    ],
                    "Top Corners" => [
                        "key" => "TOP_CORNERS",
                        "amount" => 25,
                    ],
                    "4 Corners" => [
                        "key" => "ALL_FOUR_CORNERS",
                        "amount" => 35,
                    ],
                ]
            ],
            "Choose Your Grommets (3/8 Inch)" => [
                "configKey" => "grommets",
                "values" => [
                    "None" => [
                        "key" => "NONE",
                        "amount" => 0,
                    ],
                    "Top Center" => [
                        "key" => "TOP_CENTER",
                        "amount" => 15,
                    ],
                    "Top Corners" => [
                        "key" => "TOP_CORNERS",
                        "amount" => 25,
                    ],
                    "4 Corners" => [
                        "key" => "ALL_FOUR_CORNERS",
                        "amount" => 35,
                    ],
                ]
            ],
            "Choose Grommets Color" => [
                "configKey" => "grommetsColor",
                "values" => [
                    "Silver" => [
                        "key" => "SILVER",
                        "amount" => 0,
                    ],
                    "Black" => [
                        "key" => "BLACK",
                        "amount" => 10,
                    ],
                    "Gold" => [
                        "key" => "GOLD",
                        "amount" => 20,
                    ],
                ]
            ],
            "Choose Your Frame" => [
                "configKey" => "frame",
                "values" => [
                    "No Wire Stake" => [
                        "key" => "NONE",
                        "amount" => 0,
                    ],
                    "10\"W x 30\"H Wire Stake" => [
                        "key" => "WIRE_STAKE_10X30",
                        "amount" => 40,
                    ],
                    '30”H x 10”W Wire Stake' => [
                        "key" => "WIRE_STAKE_10X30",
                        "amount" => 40,
                    ],
                ]
            ],
        ];
        $itemsSelection = [];

        foreach ($order['order_option'] as $option) {
            if (!isset($itemsSelection[$option['order_product_id']])) {
                $itemsSelection[$option['order_product_id']] = ['addonsUnitAmount' => 0, 'addonsTotalAmount' => 0];
            }
            if (in_array($option['name'], ["Choose Your Sizes", "Choose Your Sizes (WxH in Inches)", "Choose Your Sizes (Inches)"])) {
                $itemsSelection[$option['order_product_id']]['size'] = $option['value'];
                $itemsSelection[$option['order_product_id']]['price'] = $option['price'];
            }
            if ($option['name'] === "Product Image") {
                $itemsSelection[$option['order_product_id']]['productImage'] = $option['value'];
            }
            if ($option['name'] === "File") {
                $files = json_decode($option['value'], true);
                if (is_string($files)) {
                    $files = json_decode($files, true);
                }
                foreach ($files as $key => $file) {
                    $files[$key] = 'https://static.yardsignplus.com/' . $file;
                }
                $itemsSelection[$option['order_product_id']]['files'] = array_values($files);
            }
            if ($option['name'] === "Instructions") {
                $itemsSelection[$option['order_product_id']]['additionalComments'] = $option['value'];
            }
            if ($option['name'] === "Delivery Date") {
                $itemsSelection[$option['order_product_id']]['deliveryDate'] = $option['value'];
                $itemsSelection[$option['order_product_id']]['deliveryDatePrice'] = $option['price'];
            }

            if (in_array($option['name'], array_keys($addonsList))) {
                $unitAmount = round(floatval($option['price']), 2);
                if ($option['value'] == '["Front"]') {
                    $option['value'] = 'Single Sided';
                }
                if ($option['value'] == '["Front", "Back"]') {
                    $option['value'] = 'Double Sided';
                }
                $itemsSelection[$option['order_product_id']]['addons'][$addonsList[$option['name']]['configKey']] = [
                    ...$addonsConfig,
                    "key" => $addonsList[$option['name']]['values'][$option['value']]['key'],
                    "label" => $option['name'] . ' (' . $option['value'] . ')',
                    "displayText" => $option['value'],
                    "amount" => $addonsList[$option['name']]['values'][$option['value']]['amount'],
                    "unitAmount" => $unitAmount,
                ];
                $itemsSelection[$option['order_product_id']]['addonsUnitAmount'] += $unitAmount;
                $itemsSelection[$option['order_product_id']]['addonsTotalAmount'] += $unitAmount * intval($item['qty']);
            }
        }

        return $itemsSelection;
    }

    private function fetchOrders($results): array
    {
        $orders = [];
        foreach ($results as $key => $row) {
            $orders[$row['order_no']] = [
                'key' => $key + 1,
                'order' => $row,
                'customers' => $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_customers WHERE id = '" . $row['customers_id'] . "' "),
                'order_status' => $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_order_status WHERE id = '" . $row['order_status_id'] . "' "),
                'items' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_product WHERE order_id = '" . $row['order_id'] . "' "),
                'uploaded_order_proof' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_product_proof WHERE order_id = '" . $row['order_id'] . "'"),
                'order_total' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_total WHERE order_id = '" . $row['order_id'] . "'"),
                'order_history' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_history WHERE order_id = '" . $row['order_id'] . "'"),
                'shipping_history' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_history WHERE order_id = " . $row['order_id'] . " AND order_status_id IN (5,6)"),
                'order_tracking_info' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_tracking_info WHERE order_id = '" . $row['order_id'] . "'"),
                'designers_assigned' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_designers_assigned WHERE order_id = '" . $row['order_id'] . "'"),
                'order_designers_skipped' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_designers_skipped WHERE order_id = '" . $row['order_id'] . "'"),
                'order_payments' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_payment_transaction_log WHERE order_id = '" . $row['order_id'] . "'"),
                'order_payment_quick_links' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_payment_quick_links WHERE order_id = '" . $row['order_id'] . "'"),
                'order_option' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_option WHERE order_id = '" . $row['order_id'] . "'"),
                'order_canvas_designs' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_canvas_designs WHERE order_id = '" . $row['order_id'] . "'"),
                'order_charges_discounts' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_charge_discount WHERE order_id = '" . $row['order_id'] . "'"),
                'address' => $this->sourceConnection->fetchAssociative("SELECT * FROM sm3_order_address WHERE order_id = '" . $row['order_id'] . "'"),

            ];
        }
        return $orders;
    }
}
