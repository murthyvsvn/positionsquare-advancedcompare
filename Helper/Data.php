<?php
namespace Positionsquare\Advancedcompare\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ADVANCEDCOMPARE = 'advancedcompare/';
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig
                    ->getValue($field, ScopeInterface::SCOPE_STORE, $storeId);
    }
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_ADVANCEDCOMPARE .'general/'. $code, $storeId);
    }
    public function isEnable()
    {
        $isEnable = $this->getGeneralConfig('enable');
        return $isEnable;
    }
    public function getCompareCount()
    {
        $count = $this->getGeneralConfig('compare_product_count');
        return $count;
    }
}
