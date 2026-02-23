<?php

namespace App\Service\EasyPost;

use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EasyPostCustoms extends Base
{
    private bool $customCertify = true;

    private ?string $customSigner = 'Khalil Makhnojia';

    /**
     * @var string|null possible values are documents|gift|merchandise|returned_goods|sample|dangerous_goods|humanitarian_donation|other
     */
    private ?string $contentsType = 'other';

    /**
     * @var string|null possible values are none|other|quarantine|sanitary_phytosanitary_inspection
     */
    private ?string $restrictionType = 'none';

    /**
     * @var string|null
     * read more about this at https://docs.easypost.com/guides/customs-guide#step-2-create-a-customs-info-form
     */
    private ?string $eelPfc = 'NOEEI 30.37(a)';

    /**
     * @var string|null possible values are return|abandon
     */
    private ?string $nonDeliveryOption = 'return';

    private array $parcels = [];

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct($parameterBag);
    }

    public function create(bool $onlyPayload = false): array
    {
        if (count($this->getParcels()) <= 0) {
            throw new \InvalidArgumentException('At least one parcel is required to create customs');
        }

        $payload = $this->makePayload();
        if ($onlyPayload) {
            return $payload;
        }

        return [
            'success' => false,
            'message' => 'Not implemented yet',
        ];
    }

    private function makePayload(): array
    {
        $customsItems = $this->makeCustomsItems();
        return [
            'customs_certify' => $this->isCustomCertify(),
            'customs_signer' => $this->getCustomSigner(),
            'contents_type' => $this->getContentsType(),
            'restriction_type' => $this->getRestrictionType(),
            'contents_explanation' => '',
            'eel_pfc' => $this->getEelPfc(),
            'non_delivery_option' => $this->getNonDeliveryOption(),
            'customs_items' => $customsItems,
        ];
    }

    private function makeCustomsItems(): array
    {
        $customsItems = [];
        foreach ($this->getParcels() as $parcel) {
            $epParcel = $parcel['created'];
            $parcel = $parcel['original'];
            $customsItem = new EasyPostCustomsItem();
            $customsItem->setQuantity(1);
            $customsItem->setValue($parcel['value'] ?? 1);
            $customsItem->setWeight($epParcel['weight'] ?? 0);
            if (isset($parcel['sku'])) {
                $customsItem->setCode($parcel['sku']);
            }

            $customsItems[] = $customsItem->makePayload();
        }
        return $customsItems;
    }


    public function isCustomCertify(): bool
    {
        return $this->customCertify;
    }

    public function setCustomCertify(bool $customCertify): void
    {
        $this->customCertify = $customCertify;
    }

    public function getCustomSigner(): ?string
    {
        return $this->customSigner;
    }

    public function setCustomSigner(?string $customSigner): void
    {
        $this->customSigner = $customSigner;
    }

    public function getContentsType(): ?string
    {
        return $this->contentsType;
    }

    public function setContentsType(?string $contentsType): void
    {
        $this->contentsType = $contentsType;
    }

    public function getRestrictionType(): ?string
    {
        return $this->restrictionType;
    }

    public function setRestrictionType(?string $restrictionType): void
    {
        $this->restrictionType = $restrictionType;
    }

    public function getEelPfc(): ?string
    {
        return $this->eelPfc;
    }

    public function setEelPfc(?string $eelPfc): void
    {
        $this->eelPfc = $eelPfc;
    }

    public function getNonDeliveryOption(): ?string
    {
        return $this->nonDeliveryOption;
    }

    public function setNonDeliveryOption(?string $nonDeliveryOption): void
    {
        $this->nonDeliveryOption = $nonDeliveryOption;
    }

    public function getParcels(): array
    {
        return $this->parcels;
    }

    public function setParcels(array $parcels): void
    {
        $this->parcels = $parcels;
    }
}