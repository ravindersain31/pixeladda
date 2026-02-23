<?php

namespace App\Entity;

use App\Constant\CogsConstant;
use App\Constant\Editor\Addons;
use App\Entity\Admin\Coupon;
use App\Entity\Admin\ShippingInvoice;
use App\Entity\Admin\WarehouseOrder;
use App\Entity\Reports\DailyCogsReport;
use App\Entity\Reports\OrderCogsReport;
use App\Entity\Reward\RewardTransaction;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderShipmentTypeEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderChannelEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\ShippingEnum;
use App\Helper\AddressHelper;
use App\Helper\Order\MaterialCostBreakdown;
use App\Helper\ProductHelper;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;
use App\Enum\ProductEnum;
use Doctrine\Common\Collections\Criteria;
use App\Helper\PriceChartHelper;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[Vich\Uploadable]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Store $store = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    private ?AppUser $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(['apiData'])]
    private ?string $orderId = null;

    #[ORM\Column]
    private array $shippingAddress = [];

    #[ORM\Column]
    private array $billingAddress = [];

    #[ORM\Column]
    private ?bool $textUpdates = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $textUpdatesNumber = null;

    #[ORM\Column(length: 30)]
    private ?string $paymentMethod = null;

    #[ORM\Column]
    private ?bool $agreeTerms = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;

    #[ORM\Column(length: 30)]
    #[Groups(['apiData'])]
    private ?string $paymentStatus = null;

    #[ORM\Column(length: 30)]
    #[Groups(['apiData'])]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $orderAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveryDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $subTotalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $shippingAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $orderProtectionAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $internationalShippingChargeAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $adminChargeAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $couponDiscountAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $adminDiscountAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalReceivedAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderTransaction::class, orphanRemoval: true)]
    private Collection $transactions;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderMessage::class)]
    private Collection $orderMessages;

    #[ORM\ManyToOne]
    #[Groups(['apiData'])]
    private ?OrderMessage $approvedProof = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $proofApprovedAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isApproved = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Coupon $coupon = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderLog::class, orphanRemoval: true)]
    private Collection $orderLogs;

    #[ORM\ManyToOne]
    private ?AdminUser $proofDesigner = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $shippingMethod = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $shippingCarrier = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $shippingOrderId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $shippingTrackingId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $shippingMetaData = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $shippingCost = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $shippingCarrierService = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $shippingDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $paymentLinkAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $paymentLinkAmountReceived = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $chargeCardAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $refundedAmount = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['apiData'])]
    private array $metaData = [];

    #[ORM\ManyToOne]
    private ?AdminUser $cancelledBy = null;

    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?bool $isSuperRush = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $companyShippingCost = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $printFile = null;

    #[Vich\UploadableField(
        mapping: 'order_files',
        fileNameProperty: "printFile.name",
        size: "printFile.size",
        mimeType: "printFile.mimeType",
        originalName: "printFile.originalName",
        dimensions: "printFile.dimensions"
    )]
    private ?File $printFileFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $cutFile = null;

    #[Vich\UploadableField(
        mapping: 'order_files',
        fileNameProperty: "cutFile.name",
        size: "cutFile.size",
        mimeType: "cutFile.mimeType",
        originalName: "cutFile.originalName",
        dimensions: "cutFile.dimensions"
    )]
    private ?File $cutFileFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: EmailReview::class)]
    private Collection $emailReviews;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailReviewToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $leaveAReviewSentAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $printerName = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $additionalDiscount = [];

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: RewardTransaction::class)]
    private Collection $rewardTransactions;

    #[ORM\Column(nullable: true)]
    private ?int $reminderCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastReminderSent = null;

    #[ORM\OneToOne(mappedBy: 'order', cascade: ['persist', 'remove'])]
    private ?WarehouseOrder $warehouseOrder = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $shippingStatus = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isManual = null;

    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?bool $isFreightRequired = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?OrderChannelEnum $orderChannel = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subOrders')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $subOrders;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderShipment::class)]
    private Collection $orderShipments;

    #[ORM\Column(nullable: true)]
    private ?array $customsForm = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $leaveAVideoReviewSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $leaveAPhotoReviewSentAt = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: ShippingInvoice::class)]
    private Collection $shippingInvoices;

    #[ORM\Column(nullable: true)]
    private ?float $laborCost = null;

    #[ORM\Column(nullable: true)]
    private ?float $weightedAdsCost = null;

    /**
     * @var Collection<int, OrderCogsReport>
     */
    #[ORM\OneToMany(targetEntity: OrderCogsReport::class, mappedBy: 'relatedOrder')]
    private Collection $orderCogsReports;

    #[ORM\Column(nullable: true)]
    private ?int $proofApprovalCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $proofRequestChangeCountBeforeApproval = null;

    #[ORM\Column(nullable: true)]
    private ?int $proofRequestChangeCountAfterApproval = null;

    #[Groups(['apiData'])]
    #[ORM\Column(nullable: true)]
    private ?bool $isPause = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $printFilesStatus = null;

    #[ORM\ManyToOne(inversedBy: 'orders', fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    private ?StoreDomain $storeDomain = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $driveLink = null;

    #[ORM\Column(nullable: true)]
    private ?array $designerTasks = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCanvasConverted = null;

    #[ORM\Column(nullable: true)]
    private ?bool $needProof = null;

    public function __construct()
    {
        $this->version = 'V2';
        $this->setShippingAddress([]);
        $this->setBillingAddress([]);
        $this->setShippingMetaData([]);
        $this->setCustomsForm([]);
        $this->setTextUpdates(true);
        $this->setAgreeTerms(false);
        $this->orderAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->orderItems = new ArrayCollection();

        $this->setSubTotalAmount(0);
        $this->setShippingAmount(0);
        $this->setAdminChargeAmount(0);
        $this->setOrderProtectionAmount(0);
        $this->setCouponDiscountAmount(0);
        $this->setAdminDiscountAmount(0);
        $this->setTotalReceivedAmount(0);
        $this->setTotalAmount(0);
        $this->setShippingCost(0);
        $this->setPaymentLinkAmount(0);
        $this->setPaymentLinkAmountReceived(0);
        $this->setChargeCardAmount(0);
        $this->setRefundedAmount(0);
        $this->setCompanyShippingCost(0);
        $this->setReminderCount(0);

        $this->setPaymentStatus(PaymentStatusEnum::INITIATED);
        $this->setStatus(OrderStatusEnum::CREATED);
        $this->transactions = new ArrayCollection();
        $this->orderMessages = new ArrayCollection();
        $this->orderLogs = new ArrayCollection();

        $this->shippingMethod = null;
        $this->setMetaData([]);
        $this->isSuperRush = false;
        $this->emailReviews = new ArrayCollection();

        $this->emailReviewToken = bin2hex(random_bytes(15));
        $this->additionalDiscount = [];
        $this->rewardTransactions = new ArrayCollection();

        $this->isManual = false;

        $this->isFreightRequired = false;
        $this->proofApprovalCount = 0;
        $this->proofRequestChangeCountBeforeApproval = 0;
        $this->proofRequestChangeCountAfterApproval = 0;

        $this->setOrderChannel(OrderChannelEnum::CHECKOUT);
        $this->subOrders = new ArrayCollection();
        $this->setParent(null);
        $this->orderShipments = new ArrayCollection();
        $this->shippingInvoices = new ArrayCollection();
        $this->orderCogsReports = new ArrayCollection();
        $this->isCanvasConverted = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

        return $this;
    }

    public function getUser(): ?AppUser
    {
        return $this->user;
    }

    public function setUser(?AppUser $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(array $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getBillingAddress(): array
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(array $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function setAddress(string $type, array $address): self
    {
        $method = 'set' . ucfirst($type);
        if (method_exists($this, $method)) {
            return $this->$method($address);
        } else {
            throw new \BadMethodCallException("Method $method does not exist on the Order object.");
        }
    }

    public function getAddress(string $type): array
    {
        $method = 'get' . ucfirst($type);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            throw new \BadMethodCallException("Method $method does not exist on the Order object.");
        }
    }

    public function isTextUpdates(): ?bool
    {
        return $this->textUpdates;
    }

    public function setTextUpdates(bool $textUpdates): static
    {
        $this->textUpdates = $textUpdates;

        return $this;
    }

    public function getTextUpdatesNumber(): ?string
    {
        return $this->textUpdatesNumber;
    }

    public function setTextUpdatesNumber(?string $textUpdatesNumber): static
    {
        $this->textUpdatesNumber = $textUpdatesNumber;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function isAgreeTerms(): ?bool
    {
        return $this->agreeTerms;
    }

    public function setAgreeTerms(bool $agreeTerms): static
    {
        $this->agreeTerms = $agreeTerms;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->neq('product', null));
        return $this->orderItems->matching($criteria);
    }

    public function groupedItemsQtyBySizes(): array
    {
        $breakdown = new MaterialCostBreakdown($this->orderItems);
        return [
            'sizes' => $breakdown->getSizes(),
            'stakes' => $breakdown->getStakes(),
            'totalQty' => $breakdown->getTotalQuantity(),
            'largestSize' => ProductHelper::findLargestSize(array_keys($breakdown->getSizes())),
        ];
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItemsAll(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }

    public function setOrderItems(Collection $orderItems): static
    {
        $this->orderItems = $orderItems;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        if ($this->isProofApprovedBefore() && $status  === OrderStatusEnum::CHANGES_REQUESTED) {
            $this->increamentProofRequestChangeCountAfterApproval();
        } elseif ($status === OrderStatusEnum::CHANGES_REQUESTED) {
            $this->increamentProofRequestChangeCountBeforeApproval();
        }

        return $this;
    }

    public function getOrderAt(): ?\DateTimeImmutable
    {
        return $this->orderAt;
    }

    public function setOrderAt(\DateTimeImmutable $orderAt): static
    {
        $this->orderAt = $orderAt;

        return $this;
    }

    public function getDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(\DateTimeImmutable $deliveryDate): static
    {
        $this->deliveryDate = $deliveryDate;

        return $this;
    }

    public function getSubTotalAmount(): ?string
    {
        return $this->subTotalAmount;
    }

    public function setSubTotalAmount(string $subTotalAmount): static
    {
        $this->subTotalAmount = $subTotalAmount;

        return $this;
    }

    public function getShippingAmount(): ?string
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(string $shippingAmount): static
    {
        $this->shippingAmount = $shippingAmount;

        return $this;
    }

    public function getOrderProtectionAmount(): ?string
    {
        return $this->orderProtectionAmount;
    }

    public function setOrderProtectionAmount(string $orderProtectionAmount): static
    {
        $this->orderProtectionAmount = $orderProtectionAmount;

        return $this;
    }

    public function getInternationalShippingChargeAmount(): ?string
    {
        return $this->internationalShippingChargeAmount;
    }

    public function setInternationalShippingChargeAmount(string $internationalShippingChargeAmount): static
    {
        $this->internationalShippingChargeAmount = $internationalShippingChargeAmount;

        return $this;
    }

    public function getAdminChargeAmount(): ?string
    {
        return $this->adminChargeAmount;
    }

    public function setAdminChargeAmount(string $adminChargeAmount): static
    {
        $this->adminChargeAmount = $adminChargeAmount;

        return $this;
    }

    public function getCouponDiscountAmount(): ?string
    {
        return $this->couponDiscountAmount;
    }

    public function setCouponDiscountAmount(string $couponDiscountAmount): static
    {
        $this->couponDiscountAmount = $couponDiscountAmount;

        return $this;
    }

    public function getAdminDiscountAmount(): ?string
    {
        return $this->adminDiscountAmount;
    }

    public function setAdminDiscountAmount(string $adminDiscountAmount): static
    {
        $this->adminDiscountAmount = $adminDiscountAmount;

        return $this;
    }

    public function getTotalReceivedAmount(): ?string
    {
        return $this->totalReceivedAmount;
    }

    public function setTotalReceivedAmount(string $totalReceivedAmount): static
    {
        $this->totalReceivedAmount = $totalReceivedAmount;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * @return Collection<int, OrderTransaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(OrderTransaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setOrder($this);
        }

        return $this;
    }

    public function removeTransaction(OrderTransaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getOrder() === $this) {
                $transaction->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OrderMessage>
     */
    public function getOrderMessages(): Collection
    {
        return $this->orderMessages;
    }

    public function getLastOrderMessages(): bool|OrderMessage
    {
        return $this->orderMessages->last();
    }

    public function setOrderMessages(ArrayCollection $orderMessages): static
    {
        $this->orderMessages = $orderMessages;
        return $this;
    }

    public function addOrderMessage(OrderMessage $orderMessage): static
    {
        if (!$this->orderMessages->contains($orderMessage)) {
            $this->orderMessages->add($orderMessage);
            $orderMessage->setOrder($this);
        }

        return $this;
    }

    public function removeOrderMessage(OrderMessage $orderMessage): static
    {
        if ($this->orderMessages->removeElement($orderMessage)) {
            // set the owning side to null (unless already changed)
            if ($orderMessage->getOrder() === $this) {
                $orderMessage->setOrder(null);
            }
        }

        return $this;
    }

    public function getApprovedProof(): ?OrderMessage
    {
        return $this->approvedProof;
    }

    public function getApprovedProofFile($type = 'pdf'): ?UserFile
    {
        foreach ($this->approvedProof->getFiles() as $file) {
            if ($file->getType() === 'PROOF_FILE' && $type === 'pdf') {
                return $file;
            } else if ($file->getType() === 'PROOF_IMAGE' && $type === 'image') {
                return $file;
            }
        }
        return null;
    }

    public function setApprovedProof(?OrderMessage $approvedProof): static
    {
        $this->approvedProof = $approvedProof;
        $this->counterIncreament();
        return $this;
    }

    public function getProofApprovedAt(): ?\DateTimeImmutable
    {
        return $this->proofApprovedAt;
    }

    public function setProofApprovedAt(?\DateTimeImmutable $proofApprovedAt): static
    {
        $this->proofApprovedAt = $proofApprovedAt;

        return $this;
    }

    public function getIsApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(?bool $isApproved): static
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): static
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * @return Collection<int, OrderLog>
     */
    public function getOrderLogs(): Collection
    {
        return $this->orderLogs;
    }

    public function addOrderLog(OrderLog $orderLog): static
    {
        if (!$this->orderLogs->contains($orderLog)) {
            $this->orderLogs->add($orderLog);
            $orderLog->setOrder($this);
        }

        return $this;
    }

    public function removeOrderLog(OrderLog $orderLog): static
    {
        if ($this->orderLogs->removeElement($orderLog)) {
            // set the owning side to null (unless already changed)
            if ($orderLog->getOrder() === $this) {
                $orderLog->setOrder(null);
            }
        }

        return $this;
    }

    public function getProofDesigner(): ?AdminUser
    {
        return $this->proofDesigner;
    }

    public function setProofDesigner(?AdminUser $proofDesigner): static
    {
        $this->proofDesigner = $proofDesigner;

        return $this;
    }

    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(?string $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getShippingCarrier(): ?string
    {
        return $this->shippingCarrier;
    }

    public function setShippingCarrier(?string $shippingCarrier): static
    {
        $this->shippingCarrier = $shippingCarrier;

        return $this;
    }

    public function getShippingOrderId(): ?string
    {
        return $this->shippingOrderId;
    }

    public function setShippingOrderId(?string $shippingOrderId): static
    {
        $this->shippingOrderId = $shippingOrderId;

        return $this;
    }

    public function getShippingTrackingId(): ?string
    {
        return $this->shippingTrackingId;
    }

    public function setShippingTrackingId(?string $shippingTrackingId): static
    {
        $this->shippingTrackingId = $shippingTrackingId;

        return $this;
    }

    public function getShippingMetaData(): array
    {
        return $this->shippingMetaData;
    }

    public function setShippingMetaData(?array $shippingMetaData): static
    {
        $this->shippingMetaData = $shippingMetaData;

        return $this;
    }

    public function getShippingMetaDataKey(string $key)
    {
        if (isset($this->shippingMetaData[$key])) {
            return $this->shippingMetaData[$key];
        }

        return null;
    }

    public function setShippingMetaDataKey(string $key, $value): self
    {
        $metaData = $this->shippingMetaData;
        $metaData[$key] = $value;
        $this->shippingMetaData = $metaData;

        return $this;
    }

    public function getShippingCost(): ?string
    {
        return $this->shippingCost;
    }

    public function setShippingCost(string $shippingCost): static
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    public function getShippingCarrierService(): ?string
    {
        return $this->shippingCarrierService;
    }

    public function setShippingCarrierService(?string $shippingCarrierService): static
    {
        $this->shippingCarrierService = $shippingCarrierService;

        return $this;
    }

    public function getShippingDate(): ?\DateTimeInterface
    {
        return $this->shippingDate;
    }

    public function setShippingDate(?\DateTimeInterface $shippingDate): static
    {
        $this->shippingDate = $shippingDate;

        return $this;
    }

    public function getPaymentLinkAmount(): ?string
    {
        return $this->paymentLinkAmount;
    }

    public function setPaymentLinkAmount(string $paymentLinkAmount): static
    {
        $this->paymentLinkAmount = $paymentLinkAmount;

        return $this;
    }

    public function getPaymentLinkAmountReceived(): ?string
    {
        return $this->paymentLinkAmountReceived;
    }

    public function setPaymentLinkAmountReceived(string $paymentLinkAmountReceived): static
    {
        $this->paymentLinkAmountReceived = $paymentLinkAmountReceived;

        return $this;
    }

    public function getChargeCardAmount(): ?string
    {
        return $this->chargeCardAmount;
    }

    public function setChargeCardAmount(string $chargeCardAmount): static
    {
        $this->chargeCardAmount = $chargeCardAmount;

        return $this;
    }

    public function getRefundedAmount(): ?string
    {
        return $this->refundedAmount;
    }

    public function setRefundedAmount(string $refundedAmount): static
    {
        $this->refundedAmount = $refundedAmount;

        return $this;
    }

    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    public function setMetaData(?array $metaData): static
    {
        $this->metaData = $metaData;

        return $this;
    }

    public function getMetaDataKey(string $key)
    {
        if (isset($this->metaData[$key])) {
            return $this->metaData[$key];
        }

        return null;
    }

    public function setMetaDataKey(string $key, $value): self
    {
        $metaData = $this->metaData;
        $metaData[$key] = $value;
        $this->metaData = $metaData;

        return $this;
    }

    public function hasTag(string $key): bool
    {
        $tags = $this->getMetaDataKey('tags') ?? [];
        return isset($tags[$key]) && $tags[$key]['active'] === true;
    }

    public function getCancelledBy(): ?AdminUser
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?AdminUser $cancelledBy): static
    {
        $this->cancelledBy = $cancelledBy;

        return $this;
    }

    public function isIsSuperRush(): ?bool
    {
        return $this->isSuperRush;
    }

    public function setIsSuperRush(bool $isSuperRush): static
    {
        $this->isSuperRush = $isSuperRush;

        return $this;
    }

    #[Groups(['apiData'])]
    public function isIsRush(): ?bool
    {
        if (isset($this->shippingMetaData['customerShipping']['day']) && $this->shippingMetaData['customerShipping']['day'] == 3) {
            return true;
        }

        return false;
    }

    public function getVersion(): ?string
    {
        return $this->version ?? 'V2';
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getCompanyShippingCost(): ?string
    {
        return $this->companyShippingCost;
    }

    public function setCompanyShippingCost(string $companyShippingCost): static
    {
        $this->companyShippingCost = $companyShippingCost;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getTotalQuantity(): int
    {
        $totalQuantity = $this->getOrderItemsAll()->reduce(function ($carry, $item) {
            return $carry + $item->getQuantity();
        }, 0);
        return $totalQuantity;
    }

    public function isSample(): bool
    {
        foreach ($this->getOrderItems() as $orderItem) {
            $template = $orderItem->getProduct()->getParent()->getSku();

            if ($template !== 'SAMPLE') {
                $metaData = $orderItem->getMetaDataKey('customSize');
                if (!isset($metaData['parentSku']) || $metaData['parentSku'] !== 'SAMPLE') {
                    return false;
                }
            }
        }
        return true;
    }

    public function hasSample(): bool
    {
        foreach ($this->getOrderItems() as $orderItem) {
            $sku = $orderItem->getProduct()->getSku();
            if (str_contains($sku, 'SAMPLE')) {
                return true;
            }
        }
        return false;
    }

    public function isWireStake(): bool
    {
        $isContainsWireStake = false;
        foreach ($this->getOrderItems() as $orderItem) {
            $template = $orderItem->getProduct()->getParent()->getSku();
            if ($template !== 'WIRE-STAKE') {
                $isContainsWireStake = true;
            }
        }
        return !$isContainsWireStake;
    }

    public function isWireStakeAndSampleAndBlankSign(): bool
    {
        $isContainsWireStakeAndSample = false;
        foreach ($this->getOrderItems() as $orderItem) {
            $template = $orderItem->getProduct()->getParent()->getSku();
            if (!in_array($template, [ProductEnum::WIRE_STAKE->value,ProductEnum::SAMPLE->value,ProductEnum::BLANK_SIGN->value], true)) {
                $isContainsWireStakeAndSample = true;
            }
        }
        return !$isContainsWireStakeAndSample;
    }

    public function isBlankSign(): bool
    {
        $isContainsBlankSign = false;
        foreach ($this->getOrderItems() as $orderItem) {
            $template = $orderItem->getProduct()->getParent()->getSku();
            if ($template !== ProductEnum::BLANK_SIGN->value) {
                $isContainsBlankSign = true;
            }
        }
        return !$isContainsBlankSign;
    }

    public function isRequestPickup(): bool
    {
        $deliveryMethod = $this->getMetaDataKey('deliveryMethod');
        return isset($deliveryMethod['key']) && $deliveryMethod['key'] === 'REQUEST_PICKUP';
    }

    public function getPrintFile(): ?EmbeddedFile
    {
        return $this->printFile;
    }

    public function setPrintFile(EmbeddedFile $printFile): static
    {
        $this->printFile = $printFile;

        return $this;
    }

    public function getPrintFileFile(): ?File
    {
        return $this->printFileFile;
    }

    public function setPrintFileFile(?File $file = null): void
    {
        $this->printFileFile = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }


    public function getCutFile(): ?EmbeddedFile
    {
        return $this->cutFile;
    }

    public function setCutFile(EmbeddedFile $cutFile): static
    {
        $this->cutFile = $cutFile;

        return $this;
    }

    public function getCutFileFile(): ?File
    {
        return $this->cutFileFile;
    }

    public function setCutFileFile(?File $file = null): void
    {
        $this->cutFileFile = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, EmailReview>
     */
    public function getEmailReviews(): Collection
    {
        return $this->emailReviews;
    }

    public function addEmailReview(EmailReview $emailReview): static
    {
        if (!$this->emailReviews->contains($emailReview)) {
            $this->emailReviews->add($emailReview);
            $emailReview->setOrder($this);
        }

        return $this;
    }

    public function removeEmailReview(EmailReview $emailReview): static
    {
        if ($this->emailReviews->removeElement($emailReview)) {
            // set the owning side to null (unless already changed)
            if ($emailReview->getOrder() === $this) {
                $emailReview->setOrder(null);
            }
        }

        return $this;
    }

    public function getEmailReviewToken(): ?string
    {
        return $this->emailReviewToken;
    }

    public function setEmailReviewToken(?string $emailReviewToken): static
    {
        $this->emailReviewToken = $emailReviewToken;

        return $this;
    }

    public function getLeaveAReviewSentAt(): ?\DateTimeImmutable
    {
        return $this->leaveAReviewSentAt;
    }

    public function setLeaveAReviewSentAt(?\DateTimeImmutable $leaveAReviewSentAt): static
    {
        $this->leaveAReviewSentAt = $leaveAReviewSentAt;

        return $this;
    }

    public function getPrinterName(): ?string
    {
        return !empty($this->printerName) ? $this->printerName : null;
    }

    public function setPrinterName(?string $printerName): static
    {
        $this->printerName = $printerName;

        return $this;
    }

    public function getAdditionalDiscount(): ?array
    {
        return $this->additionalDiscount;
    }

    public function setAdditionalDiscount(?array $additionalDiscount): static
    {
        $this->additionalDiscount = $additionalDiscount;

        return $this;
    }

    public function getAdditionalDiscountKey(string $key): ?array
    {
        return $this->additionalDiscount[$key] ?? null;
    }

    public function setAdditionalDiscountKey(string $key, string $type = 'FIXED', float $amount = 0, string $name = 'Discount'): static
    {
        if (isset($this->additionalDiscount[$key])) {
            $this->additionalDiscount[$key]['name'] = $name;
            $this->additionalDiscount[$key]['type'] = $type;
            $this->additionalDiscount[$key]['amount'] = $amount;
        } else {
            $this->additionalDiscount[$key] = [
                'name' => $name,
                'type' => $type,
                'amount' => $amount
            ];
        }

        return $this;
    }

    public function removeAdditionalDiscountKey(string $key): static
    {
        unset($this->additionalDiscount[$key]);
        return $this;
    }

    public function getMaterialCost(): array
    {
        $breakdown = new MaterialCostBreakdown($this->orderItems);

        return [
            'precuts' => $breakdown->getPrecuts(),
            'sheets' => $breakdown->getSheets(),
            'stakes' => $breakdown->getStakes(),
            'wireStakes' => $breakdown->getWireStakeUsed(),
            'inkCost' => $breakdown->getInkCost(),
            'sheetsUsed' => $breakdown->getSheetsUsed(),
            'totalSheetsUsed' => $breakdown->getTotalSheetsUsed(),
            'totalSignsPrinted' => $breakdown->getTotalSignsPrinted(),
            'sheetsCost' => $breakdown->getSheetsCost(),
            'containerCostPrecuts' => $breakdown->getContainerCostPrecuts(),
            'containerCostFullSheets' => $breakdown->getContainerCostFullSheets(),
            'wireStakeUsed' => $breakdown->getTotalWireStakeUsed(),
            'wireStakeCost' => $breakdown->getWireStakeCost(),
            'sheetsSingleSidedPrint' => $breakdown->getSheetsSingleSidedPrint(),
            'sheetsDoubleSidedPrint' => $breakdown->getSheetsDoubleSidedPrint(),
            'totalBoxCost' => $breakdown->getTotalBoxCost(),
            'totalMaterialCost' => $breakdown->getTotalMaterialCost(),
            'materialCostBreakdown' => $breakdown->getMaterialCostBreakdown(),
            'totalLaborCost' => $breakdown->getTotalLaborCost(),
        ];
    }

    public function getShippingCosts(): array
    {
        $shippingCharges = 0;
        $shippingAdjustment = 0;
        $shippingTotal = 0;
        $shippingInvoiceFile = null;

        foreach ($this->shippingInvoices as $shippingInvoice) {
            if ($shippingInvoice->getInvoiceType() === ShippingInvoice::INVOICE_TYPE_ADJUSTMENT) {
                $shippingAdjustment += $shippingInvoice->getBilledCharge();
            } else if ($shippingInvoice->getInvoiceType() === ShippingInvoice::INVOICE_TYPE_OUTBOUND) {
                $shippingCharges += $shippingInvoice->getBilledCharge();
            }
            $shippingTotal += $shippingInvoice->getBilledCharge();
            $shippingInvoiceFile = $shippingInvoice->getFile();
        }

        return [
            'shippingCharges' => $shippingCharges,
            'shippingAdjustment' => $shippingAdjustment,
            'shippingTotal' => $shippingTotal,
            'shippingInvoiceFile' => $shippingInvoiceFile,
            'carrier' => $shippingInvoiceFile ? $shippingInvoiceFile->getCarrier() ?? 'CSV' : ''
        ];
    }

    public function getShippingCostByOrder(string $invoiceType, ?string $trackingId): float
    {
        if (empty($invoiceType) || empty($trackingId)) {
            return 0;
        }

        if (!in_array($invoiceType, [ShippingInvoice::INVOICE_TYPE_ADJUSTMENT, ShippingInvoice::INVOICE_TYPE_OUTBOUND, ShippingInvoice::INVOICE_TYPE_TOTAL])) {
            return 0;
        }

        if ($invoiceType === ShippingInvoice::INVOICE_TYPE_TOTAL) {
            $data = $this->shippingInvoices->filter(fn(ShippingInvoice $shippingInvoice) => $shippingInvoice->getTrackingNumber() === $trackingId);
            return array_sum($data->map(fn(ShippingInvoice $shippingInvoice) => $shippingInvoice->getBilledCharge())->toArray()) ?? 0;
        }

        $data = $this->shippingInvoices->filter(fn(ShippingInvoice $shippingInvoice) => $shippingInvoice->getInvoiceType() === $invoiceType && $shippingInvoice->getTrackingNumber() === $trackingId);

        return array_sum($data->map(fn(ShippingInvoice $shippingInvoice) => $shippingInvoice->getBilledCharge())->toArray()) ?? 0;
    }


    public function isIsYSPLogo(): bool {
        foreach ($this->orderItems as $orderItem) {
            if($orderItem->getMetaDataKey('customArtwork')) {
                foreach ($orderItem->getMetaDataKey('customArtwork') as $artwork_type => $sides) {
                    if ($artwork_type === 'YSP-LOGO') {
                        if ((isset($sides['front']) && count($sides['front']) > 0) ||
                            (isset($sides['back']) && count($sides['back']) > 0)) {
                            return true;
                        }
                    }
                }
            }

            if (isset($orderItem->getCanvasData()['front']['objects'])) {
                foreach ($orderItem->getCanvasData()['front']['objects'] as $object) {
                    if (isset($object['custom'], $object['custom']['id']) && $object['custom']['id'] === 'ysp-logo') {
                        return true;
                    }
                }
            }

            if (isset($orderItem->getCanvasData()['back']['objects'])) {
                foreach ($orderItem->getCanvasData()['back']['objects'] as $object) {
                    if (isset($object['custom'], $object['custom']['id']) && $object['custom']['id'] === 'ysp-logo') {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @return Collection<int, RewardTransaction>
     */
    public function getRewardTransactions(): Collection
    {
        return $this->rewardTransactions;
    }

    public function addRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if (!$this->rewardTransactions->contains($rewardTransaction)) {
            $this->rewardTransactions->add($rewardTransaction);
            $rewardTransaction->setOrder($this);
        }

        return $this;
    }

    public function removeRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if ($this->rewardTransactions->removeElement($rewardTransaction)) {
            // set the owning side to null (unless already changed)
            if ($rewardTransaction->getOrder() === $this) {
                $rewardTransaction->setOrder(null);
            }
        }

        return $this;
    }

    public function getReminderCount(): ?int
    {
        return $this->reminderCount;
    }

    public function setReminderCount(?int $reminderCount): static
    {
        $this->reminderCount = $reminderCount;

        return $this;
    }

    public function getLastReminderSent(): ?\DateTimeInterface
    {
        return $this->lastReminderSent;
    }

    public function setLastReminderSent(?\DateTimeInterface $lastReminderSent): static
    {
        $this->lastReminderSent = $lastReminderSent;

        return $this;
    }

    #[Groups(['apiData'])]
    public function getTotalQuantities(): array
    {
        $totalQuantity = 0;
        $frameQuantity = 0;
        $frameTypes = [];
        $frameTypeQty = [];
        $sizes = [];
        $quantitiesBySize = [];
        $sides = 'SS';
        $grommets = 'Grommets: None';
        foreach ($this->getOrderItems() as $orderItem) {
            $sku = $orderItem->getProduct()->getParent()->getSku();
            $customSize = $orderItem->getMetaDataKey('customSize');
            $isWireStake = $orderItem->getMetaDataKey('isWireStake');
            if (is_array($customSize) && !$isWireStake) {
                $size = $customSize['templateSize']['width'] . 'x' . $customSize['templateSize']['height'];
            } else {
                $size = $orderItem->getProduct()->getName();
            }
            $quantity = $orderItem->getQuantity();
            $sizes[] = $size;
            $quantitiesBySize[$size] = ($quantitiesBySize[$size] ?? 0) + $quantity; 
            if ($sku === 'WIRE-STAKE' || $isWireStake) {
                $frameQuantity += $quantity;
                $frameKey = $size;
                $frameQty = $quantity;
                $frameTypeQty[$frameKey] = ($frameTypeQty[$frameKey] ?? 0) + $frameQty;
                $frameTypes[$frameKey] = ($frameTypes[$frameKey] ?? 0) + $frameQty;
            } else {
                $totalQuantity += $quantity;
            }
            $addOns = $orderItem->getAddOns();
            if(isset($addOns['frame']) && Addons::hasSubAddon($addOns['frame'])){
                foreach ($addOns['frame'] as $key => $addon) {
                    // if (str_contains($addon['key'], 'PREMIUM') || str_contains($addon['key'], 'SINGLE')) {
                        $frameKey = $addon['key'];
                        $frameQty = $addon['quantity'] ?? $addon['totalQuantity'] ?? $quantity;
                        $frameTypeQty[$frameKey] = ($frameTypeQty[$frameKey] ?? 0) + $frameQty;
                        $frameQuantity += $frameQty;
                        $frameTypes[$frameKey] = ($frameTypes[$frameKey] ?? 0) + $frameQty;
                    // }
                }
            }
            if (isset($addOns['frame']['key']) && $addOns['frame']['key'] !== 'NONE') {
                // if (str_contains($addOns['frame']['key'], 'PREMIUM') || str_contains($addOns['frame']['key'], 'SINGLE')) {
                    $frameKey = $addOns['frame']['key'];
                    $frameQty = $quantity;
                    $frameTypeQty[$frameKey] = ($frameTypeQty[$frameKey] ?? 0) + $frameQty;
                    $frameQuantity += $frameQty;
                    $frameTypes[$frameKey] = ($frameTypes[$frameKey] ?? 0) + $frameQty;
                // }
            }
            if (isset($addOns['sides']['key']) && $addOns['sides']['key'] === 'DOUBLE') {
                $sides = 'DS';
            }
            if (isset($addOns['grommets']['key']) && $addOns['grommets']['key'] !== 'NONE') {
                $grommets = $addOns['grommets']['displayText'];
            }
        }

        $frameType = null;
        if (!empty($frameTypes)) {
            $frameType = implode(', ', array_map(
                function ($key, $value) {
                    // if (preg_match('/PREMIUM|SINGLE|STANDARD/', $key, $matches)) {
                    //     return "{$value} " . ucfirst(strtolower($matches[0]));
                    // }
                    return "{$value}" .' '. Addons::getFrameQuantityType($key);
                },
                array_keys($frameTypeQty),
                $frameTypeQty
            ));
        }

        return [
            'totalQuantity' => $totalQuantity,
            'frameQuantity' => $frameQuantity,
            'frameTypeQty' => $frameTypeQty,
            'frameType' => $frameType,
            'sizes' => array_unique($sizes),
            'quantitiesBySize' => $quantitiesBySize,
            'sides' => $sides,
            'grommets' => $grommets,
        ];
    }

    public function getWarehouseOrder(): ?WarehouseOrder
    {
        return $this->warehouseOrder;
    }

    public function setWarehouseOrder(WarehouseOrder $warehouseOrder): static
    {
        // set the owning side of the relation if necessary
        if ($warehouseOrder->getOrder() !== $this) {
            $warehouseOrder->setOrder($this);
        }

        $this->warehouseOrder = $warehouseOrder;

        return $this;
    }

    public function getShippingStatus(): ?string
    {
        return $this->shippingStatus;
    }

    public function setShippingStatus(?string $shippingStatus): static
    {
        $this->shippingStatus = $shippingStatus;

        return $this;
    }

    public function isIsManual(): ?bool
    {
        return $this->isManual;
    }

    public function setIsManual(?bool $isManual): static
    {
        $this->isManual = $isManual;

        return $this;
    }

    public function isIsFreightRequired(): ?bool
    {
        return $this->isFreightRequired;
    }

    public function setIsFreightRequired(bool $isFreightRequired): static
    {
        $this->isFreightRequired = $isFreightRequired;

        return $this;
    }

    public function getOrderChannel(): ?OrderChannelEnum
    {
        if ($this->orderChannel) {
            return $this->orderChannel;
        }
        return OrderChannelEnum::CHECKOUT;
    }

    public function setOrderChannel(?OrderChannelEnum $orderChannel): static
    {
        $this->orderChannel = $orderChannel;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubOrders(): Collection
    {
        return $this->subOrders;
    }

    public function addSubOrder(self $subOrder): static
    {
        if (!$this->subOrders->contains($subOrder)) {
            $this->subOrders->add($subOrder);
            $subOrder->setParent($this);
        }

        return $this;
    }

    public function removeSubOrder(self $subOrder): static
    {
        if ($this->subOrders->removeElement($subOrder)) {
            // set the owning side to null (unless already changed)
            if ($subOrder->getParent() === $this) {
                $subOrder->setParent(null);
            }
        }

        return $this;
    }

    public function isAllowPushToSE(): bool
    {
        return $this->getStatus() === OrderStatusEnum::SENT_FOR_PRODUCTION &&
            $this->getShippingOrderId() == null &&
            $this->getWarehouseOrder() &&
            $this->getWarehouseOrder()->getPrintStatus() === WarehouseOrderStatusEnum::DONE;

    }

    public function isOrderInOQ(): bool
    {
        $orderStatusAllowed = in_array($this->getStatus(), [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED]);
        return $orderStatusAllowed && $this->getWarehouseOrder() &&
            $this->getWarehouseOrder()->getPrinterName() !== null &&
            $this->getWarehouseOrder()->getShipBy() !== null &&
            $this->getWarehouseOrder()->getPrintStatus() !== WarehouseOrderStatusEnum::DONE;
    }

    public function isOrderReadyForOQ(): bool
    {
        $orderStatusAllowed = in_array($this->getStatus(), [OrderStatusEnum::PROOF_APPROVED]);
        return $orderStatusAllowed && $this->getWarehouseOrder() &&
            $this->getWarehouseOrder()->getShipBy() === null &&
            $this->getWarehouseOrder()->getPrintStatus() !== WarehouseOrderStatusEnum::DONE;
    }

    public function isAllowedToMoveToUploadProof(): bool
    {
        $orderStatusAllowed = in_array($this->getStatus(), [OrderStatusEnum::PROOF_APPROVED, OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT]);
        $ifOrderHasOQReference = $orderStatusAllowed && !$this->getWarehouseOrder();

        return $ifOrderHasOQReference || ($orderStatusAllowed &&
                $this->getWarehouseOrder() &&
                $this->getWarehouseOrder()->getPrintStatus() === WarehouseOrderStatusEnum::READY);
    }

    /**
     * @return Collection<int, OrderShipment>
     */
    public function getOrderShipments(): Collection
    {
        return $this->orderShipments->filter(fn(OrderShipment $orderShipment) => !$orderShipment->getRefundedAt());
    }

    public function orderShipmentsByBatch(?OrderShipmentTypeEnum $shipmentTypeEnum = null): Collection
    {
        $batches = [];
        foreach ($this->getOrderShipments() as $orderShipment) {
            if ($orderShipment->getRefundedAt()) {
                continue;
            }
            if ($shipmentTypeEnum && $orderShipment->getType() !== $shipmentTypeEnum) {
                continue;
            }
            $batch = 'B' . $orderShipment->getBatchNum();
            if (!isset($batches[$batch])) {
                $batches[$batch] = new ArrayCollection();
            }
            $batches[$batch]->add($orderShipment);
        }
        return new ArrayCollection($batches);
    }


    public function addOrderShipment(OrderShipment $orderShipment): static
    {
        if (!$this->orderShipments->contains($orderShipment)) {
            $this->orderShipments->add($orderShipment);
            $orderShipment->setOrder($this);
        }

        return $this;
    }

    public function removeOrderShipment(OrderShipment $orderShipment): static
    {
        if ($this->orderShipments->removeElement($orderShipment)) {
            // set the owning side to null (unless already changed)
            if ($orderShipment->getOrder() === $this) {
                $orderShipment->setOrder(null);
            }
        }

        return $this;
    }

    public function isOrderShipmentHaveSameTracking(): bool
    {
        $orderShipmentTracking = [];
        foreach ($this->getOrderShipments() as $orderShipment) {
            $orderShipmentTracking[] = $orderShipment->getTrackingId();
        }
        $orderShipmentTracking = array_unique($orderShipmentTracking);
        return count($orderShipmentTracking) === 1;
    }

    public function isInternational(): bool
    {
        return $this->getShippingAddress()['country'] !== 'US';
    }

    public function getCustomsForm(): ?array
    {
        return $this->customsForm;
    }

    public function setCustomsForm(?array $customsForm): static
    {
        $this->customsForm = $customsForm;

        return $this;
    }

    public function isCustomsPending(): bool
    {
        return $this->isInternational() && (!$this->getCustomsForm() || count($this->getCustomsForm()) <= 0);
    }

    public function getLeaveAVideoReviewSentAt(): ?\DateTimeImmutable
    {
        return $this->leaveAVideoReviewSentAt;
    }

    public function setLeaveAVideoReviewSentAt(?\DateTimeImmutable $leaveAVideoReviewSentAt): static
    {
        $this->leaveAVideoReviewSentAt = $leaveAVideoReviewSentAt;

        return $this;
    }

    public function getLeaveAPhotoReviewSentAt(): ?\DateTimeImmutable
    {
        return $this->leaveAPhotoReviewSentAt;
    }

    public function setLeaveAPhotoReviewSentAt(?\DateTimeImmutable $leaveAPhotoReviewSentAt): static
    {
        $this->leaveAPhotoReviewSentAt = $leaveAPhotoReviewSentAt;

        return $this;
    }

    #[Groups(['apiData'])]
    public function isSplitOrder(): bool
    {
        return $this->getOrderChannel() === OrderChannelEnum::SPLIT_ORDER;
    }

    #[Groups(['apiData'])]
    public function getSplitOrderTag(): ?string
    {
        if ($this->parent && $this->isSplitOrder()) {
            $parts = explode('-', $this->orderId);
            if (count($parts) > 1 && isset($parts[1])) {
                return sprintf('<span class="badge bg-danger">SPLIT %s</span>', $parts[1]);
            }
        }

        return null;
    }

    #[Groups(['apiData'])]
    public function getSplitOrderTagOnly(): ?string
    {
        if ($this->parent && $this->isSplitOrder()) {
            $parts = explode('-', $this->orderId);
            if (count($parts) > 1 && isset($parts[1])) {
                return sprintf('SPLIT %s', $parts[1]);
            }
        }

        return null;
    }

    /**
     * @return Collection<int, ShippingInvoice>
     */
    public function getShippingInvoices(): Collection
    {
        return $this->shippingInvoices;
    }

    public function addShippingInvoice(ShippingInvoice $shippingInvoice): static
    {
        if (!$this->shippingInvoices->contains($shippingInvoice)) {
            $this->shippingInvoices->add($shippingInvoice);
            $shippingInvoice->setOrder($this);
        }

        return $this;
    }

    public function removeShippingInvoice(ShippingInvoice $shippingInvoice): static
    {
        if ($this->shippingInvoices->removeElement($shippingInvoice)) {
            // set the owning side to null (unless already changed)
            if ($shippingInvoice->getOrder() === $this) {
                $shippingInvoice->setOrder(null);
            }
        }

        return $this;
    }

    public function getGrossMargin(): float
    {
        $marginMaterialCost = $this->getMaterialCost()['totalMaterialCost'] ?? 0;
        $refundedAmount = $this->getRefundedAmount() ?? 0;
        $totalLaborCost = $this->getTotalLaborCost() ?? 0;

        return $this->totalReceivedAmount - ($marginMaterialCost + $refundedAmount + $this->getTotalShippingCost() + $totalLaborCost);
    }

    public function getGrossMarginPercentage(): float
    {
        $grossMarginPercentage = $this->totalReceivedAmount != 0 ? (((float) $this->getGrossMargin()) / $this->totalReceivedAmount) * 100 : 0;

        return $grossMarginPercentage;
    }

    public function getNetMargin(): float
    {
        $marginMaterialCost = $this->getMaterialCost()['totalMaterialCost'] ?? 0;
        $refundedAmount = $this->getRefundedAmount() ?? 0;
        $totalLaborCost = $this->getTotalLaborCost() ?? 0;

        return $this->totalReceivedAmount - ($this->getTotalShippingCost() + $marginMaterialCost + $refundedAmount + $totalLaborCost + $this->weightedAdsCost);
    }

    public function getNetMarginPercentage(): float
    {
        $netMarginPercentage = $this->totalReceivedAmount != 0 ? (((float) $this->getNetMargin()) / $this->totalReceivedAmount) * 100 : 0;
        return $netMarginPercentage;
    }

    public function getLaborCost(): ?float
    {
        return $this->laborCost;
    }

    public function setLaborCost(?float $laborCost): static
    {
        $this->laborCost = $laborCost;

        return $this;
    }

    public function getTotalLaborCost(): ?float
    {
        // TODO: uncomment after labor cost is calculated
        if ($this->laborCost > 0) {
            return $this->laborCost;
        }

        $laborCost = $this->getMaterialCost()['totalLaborCost'] ?? 0;

        return $laborCost;
    }

    public function getTotalShippingCharges(): ?float
    {
        $shippingCosts = $this->getShippingCosts();
        if (isset($shippingCosts['shippingCharges']) && $shippingCosts['shippingCharges'] > 0) {
            return $shippingCosts['shippingCharges'];
        }

        return $this->shippingCost;
    }

    public function getTotalShippingCost(): ?float
    {
        $shippingCosts = $this->getShippingCosts();
        $shippingAdjustment = $shippingCosts['shippingAdjustment'] ?? 0;
        if (isset($shippingCosts['shippingCharges']) && $shippingCosts['shippingCharges'] > 0) {
            return $shippingCosts['shippingCharges'] + $shippingAdjustment;
        }

        return $this->shippingCost + $shippingAdjustment;
    }

    public function getProfitAndLoss(): float
    {
        $materialCost = $this->getMaterialCost()['totalMaterialCost'] ?? 0;
        return ($this->totalReceivedAmount) - ($this->refundedAmount + $this->getTotalShippingCost() + $materialCost + $this->getTotalLaborCost() + $this->weightedAdsCost);
    }

    public function getWeightedAdsCost(): ?float
    {
        return $this->weightedAdsCost;
    }

    public function setWeightedAdsCost(?float $weightedAdsCost): static
    {
        $this->weightedAdsCost = $weightedAdsCost;

        return $this;
    }

    /**
     * @return Collection<int, OrderCogsReport>
     */
    public function getOrderCogsReports(): Collection
    {
        return $this->orderCogsReports;
    }

    public function addOrderCogsReport(OrderCogsReport $orderCogsReport): static
    {
        if (!$this->orderCogsReports->contains($orderCogsReport)) {
            $this->orderCogsReports->add($orderCogsReport);
            $orderCogsReport->setRelatedOrder($this);
        }

        return $this;
    }

    public function removeOrderCogsReport(OrderCogsReport $orderCogsReport): static
    {
        if ($this->orderCogsReports->removeElement($orderCogsReport)) {
            // set the owning side to null (unless already changed)
            if ($orderCogsReport->getRelatedOrder() === $this) {
                $orderCogsReport->setRelatedOrder(null);
            }
        }

        return $this;
    }

    public function getProofApprovalCount(): int
    {
        return $this->proofApprovalCount ?? 0;
    }

    public function setProofApprovalCount(int $proofApprovalCount): void
    {
        $this->proofApprovalCount = $proofApprovalCount;
    }

    public function isProofApprovedBefore(): bool
    {
        return $this->proofApprovalCount > 0;
    }

    public function incrementProofApprovalCount(): void
    {
        $this->proofApprovalCount++;
    }

    public function getMaxAllowedRequestChanges(): int|null
    {
        return $this->isProofApprovedBefore() ? OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_AFTER_APPROVAL : OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_BEFORE_APPROVAL;
    }

    public function hasReachedRequestChangesLimit(): bool
    {
        if ($this->isProofApprovedBefore()) {
            return $this->proofRequestChangeCountAfterApproval === OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_AFTER_APPROVAL;
        }
        return $this->proofRequestChangeCountBeforeApproval === OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_BEFORE_APPROVAL;
    }

    public function hasExceededRequestChangesLimit(): bool
    {
        if ($this->isProofApprovedBefore()) {
            return $this->proofRequestChangeCountAfterApproval > OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_AFTER_APPROVAL;
        }
        return $this->proofRequestChangeCountBeforeApproval > OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_BEFORE_APPROVAL;
    }

    public function getRemainingRequestChanges(): int
    {
        return $this->isProofApprovedBefore() ? $this->getMaxAllowedRequestChanges() - $this->proofRequestChangeCountAfterApproval : $this->getMaxAllowedRequestChanges() - $this->proofRequestChangeCountAfterApproval;
    }

    public function hasReachedApprovalLimit(): bool
    {
        return $this->getProofApprovalCount() === OrderStatusEnum::MAX_APPROVALS_COUNT;
    }

    public function hasExceededApprovalLimit(): bool
    {
        return $this->getProofApprovalCount() > OrderStatusEnum::MAX_APPROVALS_COUNT;
    }

    /**
     * Helper to count any order messages by type.
     */
    public function countOrderMessagesByType(string $type): int
    {
        return $this->orderMessages->filter(
            fn(OrderMessage $message) => $message->getType() === $type
        )->count();
    }

    public function getProofRequestChangeCountBeforeApproval(): ?int
    {
        return $this->proofRequestChangeCountBeforeApproval ?? 0;
    }

    public function setProofRequestChangeCountBeforeApproval(?int $proofRequestChangeCountBeforeApproval): static
    {
        $this->proofRequestChangeCountBeforeApproval = $proofRequestChangeCountBeforeApproval;

        return $this;
    }

    public function increamentProofRequestChangeCountBeforeApproval(): void
    {
        $this->proofRequestChangeCountBeforeApproval++;
    }

    public function getProofRequestChangeCountAfterApproval(): ?int
    {
        return $this->proofRequestChangeCountAfterApproval ?? 0;
    }

    public function setProofRequestChangeCountAfterApproval(?int $proofRequestChangeCountAfterApproval): static
    {
        $this->proofRequestChangeCountAfterApproval = $proofRequestChangeCountAfterApproval;

        return $this;
    }

    public function increamentProofRequestChangeCountAfterApproval(): void
    {
        $this->proofRequestChangeCountAfterApproval++;
    }

    public function counterIncreament(): void
    {
        if($this->approvedProof instanceof OrderMessage && $this->approvedProof->getType() === OrderStatusEnum::PROOF) {
            $this->incrementProofApprovalCount();
            if ($this->getProofApprovalCount() <= 1) {
                $this->increamentProofRequestChangeCountAfterApproval();
            }
        }
    }

    public function isPause(): ?bool
    {
        return $this->isPause ?? false;
    }

    public function setIsPause(?bool $isPause): static
    {
        $this->isPause = $isPause;

        return $this;
    }

    public function getPrintFilesStatus(): ?string
    {
        return $this->printFilesStatus;
    }

    public function setPrintFilesStatus(?string $printFilesStatus): static
    {
        $this->printFilesStatus = $printFilesStatus;

        return $this;
    }

    public function getStoreDomain(): ?StoreDomain
    {
        return $this->storeDomain;
    }

    public function setStoreDomain(?StoreDomain $storeDomain): static
    {
        $this->storeDomain = $storeDomain;

        return $this;
    }

    public function getDriveLink(): ?string
    {
        return $this->driveLink;
    }

    public function setDriveLink(?string $driveLink): static
    {
        $this->driveLink = $driveLink;

        return $this;
    }

    public function getDesignerTasks(): ?array
    {
        return $this->designerTasks;
    }

    public function setDesignerTasks(?array $designerTasks): static
    {
        $this->designerTasks = $designerTasks;

        return $this;
    }

    public function isNeedProof(): bool
    {
        return $this->needProof !== false;
    }

    public function setNeedProof(?bool $needProof): static
    {
        $this->needProof = $needProof;

        return $this;
    }

    public function isCanvasConverted(): bool
    {
        return $this->isCanvasConverted === true;
    }

    public function setIsCanvasConverted(?bool $isCanvasConverted): static
    {
        $this->isCanvasConverted = $isCanvasConverted;

        return $this;
    }

}