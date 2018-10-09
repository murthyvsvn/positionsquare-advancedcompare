<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Positionsquare\Advancedcompare\Block\Index;

class Index extends \Magento\Framework\View\Element\Template
{
    public $helperData;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Positionsquare\Advancedcompare\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperData = $helperData;
    }
}
