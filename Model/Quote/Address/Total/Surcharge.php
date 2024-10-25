<?php
declare(strict_types=1);

namespace RadWorks\ShipStationCarrierSurcharge\Model\Quote\Address\Total;

use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use RadWorks\ShipStationCarrier\Api\Data\Service\ConfigInterface as ServiceConfigInterface;
use RadWorks\ShipStationCarrier\Api\ServiceProviderInterface;
use RadWorks\ShipStationCarrier\Model\Carrier\ConfigInterface;

class Surcharge extends AbstractTotal
{
    public const TOTAL_CODE = 'shipstation_surcharge';

    /**
     * @var PriceCurrencyInterface $priceCurrency
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var ServiceProviderInterface $serviceProvider
     */
    private ServiceProviderInterface $serviceProvider;

    /**
     * Constructor
     *
     * @param PriceCurrencyInterface $priceCurrency
     * @param ServiceProviderInterface $serviceProvider
     */
    public function __construct(PriceCurrencyInterface $priceCurrency, ServiceProviderInterface $serviceProvider)
    {
        $this->priceCurrency = $priceCurrency;
        $this->serviceProvider = $serviceProvider;
        $this->setCode(self::TOTAL_CODE);
    }

    /**
     * Collect totals information about shipping
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total): static
    {
        parent::collect($quote, $shippingAssignment, $total);
        /** @var Address $address */
        $address = $shippingAssignment->getShipping()->getAddress();
        $serviceConfig = $this->getServiceConfig($quote, $shippingAssignment);
        $baseSurchargeRate = $serviceConfig->getSubtotalAdjustmentRate() ?: 0;
        $baseSurchargeAmount = $baseSurchargeRate ? $total->getBaseTotalAmount('subtotal') * $baseSurchargeRate : 0;
        $surchargeAmount = $baseSurchargeAmount ? $this->priceCurrency->convert(
            $baseSurchargeAmount,
            $quote->getStore()
        ) : 0;
        $address->setShippingSurchargeLabel($serviceConfig->getSubtotalAdjustmentLabel());
        $address->setShippingSurchargeAmount($surchargeAmount);
        $address->setBaseShippingSurchargeAmount($baseSurchargeAmount);
        $total->setTotalAmount($this->getCode(), $surchargeAmount);
        $total->setBaseTotalAmount($this->getCode(), $baseSurchargeAmount);

        return $this;
    }

    /**
     * Add totals information to address object
     *
     * @param Quote $quote
     * @param Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(Quote $quote, Total $total): array
    {
        $surchargeAmount = $quote->getShippingAddress()->getShippingSurchargeAmount();
        $surchargeLabel = $quote->getShippingAddress()->getShippingSurchargeLabel();
        if ($quote->isVirtual() || !$surchargeAmount) {
            return [];
        }

        return [
            'code' => $this->getCode(),
            'title' => $surchargeLabel ?: __('Surcharge'),
            'value' => (float)$surchargeAmount,
        ];
    }

    /**
     * Get total label
     *
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __('Surcharge');
    }

    /**
     * Get surcharge rate
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @return ServiceConfigInterface|null
     */
    private function getServiceConfig(Quote $quote, ShippingAssignmentInterface $shippingAssignment): ?ServiceConfigInterface
    {
        $shippingMethod = $shippingAssignment->getShipping()->getMethod() ?: $quote->getShippingAddress()->getShippingMethod();
        if (!($shippingMethod && $shippingAssignment->getItems())) {
            return null;
        }

        foreach ($this->serviceProvider->getAllowedServices() as $service) {
            if (ConfigInterface::CARRIER_CODE . '_' . $service->getCode() !== $shippingMethod) {
                continue;
            }

            /** @var ServiceConfigInterface $serviceConfig */
            if (!$serviceConfig = $service->getExtensionAttributes()->getConfig()) {
                break;
            }

            return $serviceConfig;
        }

        return null;
    }
}
