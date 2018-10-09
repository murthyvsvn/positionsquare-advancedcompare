<?php
namespace Positionsquare\Advancedcompare\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Resetcompare extends \Magento\Framework\App\Action\Action
{
    public $resultPageFactory = false;
    public $customerSession;
    public $customerVisitor;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Catalog\Model\Product\Compare\ListCompare $listCompare,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Visitor $customerVisitor
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->listCompare = $listCompare;
        $this->customerSession = $customerSession;
        $this->customerVisitor = $customerVisitor;
    }
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('catalog_compare_item');
        $visitorId = (!empty($this->customerVisitor->getId()) ? $this->customerVisitor->getId() : null);
        $storeId = $storeManager->getStore()->getStoreId();
        $customerSessionId = $this->customerSession->getCustomer()->getId();
        $customerId = (!empty($customerSessionId) ? $customerSessionId : null);
        if (!empty($customerId)) {
            $removeQuery = "delete from " . $tableName." where customer_id='".$customerId."'";
        } else {
            $removeQuery = "delete from " . $tableName." where visitor_id='".$visitorId."'";
        }

        $connection->query($removeQuery);
        $customerField = ($customerId === null) ? "customer_id is NULL" : "customer_id = '".$customerId."'";
        $getCompareList = "select product_id from ".$tableName.
                          " where visitor_id='".$visitorId.
                          "' and ".$customerField;
        $CompareListRows = $connection->fetchAll($getCompareList);
        $compareProducts = [];
        foreach ($CompareListRows as $CompareListRow) {
            $compareProduct = $CompareListRow['product_id'];
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($compareProduct);
            $productImage = $product->getThumbnail();
            if ($productImage == "") {
                $media_url = $placeholderImageUrl;
            } else {
                $media_url = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
                                           ->getStore()
                                           ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).
                                           "catalog/product".$product->getData('thumbnail');
            }

            $productName = $product->getName();
            $productId = $product->getId();
            $innerArray = [$productName,$media_url,$productId];
            array_push($compareProducts, $innerArray);
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['compareList'=> $compareProducts]);
        return $resultJson;
    }
}
