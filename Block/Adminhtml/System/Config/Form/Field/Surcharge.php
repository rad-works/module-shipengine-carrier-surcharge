<?php
declare(strict_types=1);

namespace RadWorks\ShipEngineCarrierSurcharge\Block\Adminhtml\System\Config\Form\Field;

use RadWorks\ShipEngineCarrier\Api\Data\Service\ConfigInterface;
use RadWorks\ShipEngineCarrier\Block\Adminhtml\System\Config\Form\Field\GenericFieldArray;
use RadWorks\ShipEngineCarrier\Block\Adminhtml\System\Config\Form\Field\Renderer\AllowedMethods;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class Surcharge extends GenericFieldArray
{
    /**
     * Prepare to render
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn(ConfigInterface::FIELD_SERVICE, [
            'label' => __('Method'),
            'renderer' => $this->getRenderer(AllowedMethods::class)->setExtraParams('style="width:180px"')
        ]);
        $this->addColumn(ConfigInterface::FIELD_SUBTOTAL_ADJUSTMENT_LABEL, [
            'label' => __('Label'),
            'class' => 'required-entry validate-text validate-no-html-tags admin__control-text'
        ]);
        $this->addColumn(ConfigInterface::FIELD_SUBTOTAL_ADJUSTMENT_RATE, [
            'label' => __('Rate Adjustment'),
            'class' => 'required-entry validate-number admin__control-text'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        if ($serviceCode = $row->getData(ConfigInterface::FIELD_SERVICE)) {
            $options[$this->calculateOptionHash(AllowedMethods::class, $serviceCode)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }
}
