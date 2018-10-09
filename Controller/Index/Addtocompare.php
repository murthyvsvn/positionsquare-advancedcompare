<?php
namespace Positionsquare\Advancedcompare\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Addtocompare extends \Magento\Framework\App\Action\Action
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
        $_helper = $objectManager->get('Positionsquare\Advancedcompare\Helper\Data');
        $compareCountRest = $_helper->getCompareCount();
        $imageHelper = $objectManager->get(\Magento\Catalog\Helper\Image::class);
        $placeholderImageUrl = $imageHelper->getDefaultPlaceholderUrl('image');
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('catalog_compare_item');
        $visitorId = (!empty($this->customerVisitor->getId()) ? $this->customerVisitor->getId() : null);
        $storeId = $storeManager->getStore()->getStoreId();
        $customerSessionId = $this->customerSession->getCustomer()->getId();
        $customerId = (!empty($customerSessionId) ? $customerSessionId : null);
        $postData= $this->getRequest()->getPostValue();
        $productIds = $postData['productid'];
        $customerField = ($customerId === null) ? "customer_id is NULL" : "customer_id = '".$customerId."'";
        if (!empty($customerId)) {
            $getCompareCount = "select product_id from ".$tableName.
                               " where visitor_id='".$visitorId.
                               "' and ".$customerField.
                               " order by 'product_id' desc limit ".$compareCount;
        } else {
            $getCompareCount = "select product_id from ".$tableName.
                               " where visitor_id='".$visitorId.
                               "' and ".$customerField;
        }

        $compareCount = $connection->fetchAll($getCompareCount);
        if (count($compareCount) < $compareCountRest) {
            $checkCompareList = "select * from ".$tableName.
                                " where visitor_id='".$visitorId.
                                "' and product_id='".$productIds."'";
            $row = $connection->fetchRow($checkCompareList);
            if (empty($row)) {
                if (is_null($customerId)) {
                    $addProductToCompareList = "insert Into " .$tableName.
                                               " set visitor_id='".$visitorId."', product_id='".$productIds.
                                               "', store_id='".$storeId."'";
                } else {
                    $addProductToCompareList = "insert Into " .$tableName.
                                               " set visitor_id='".$visitorId.
                                               "', customer_id='".$customerId.
                                               "', product_id='".$productIds.
                                               "', store_id='".$storeId."'";
                }

                $connection->query($addProductToCompareList);
            }

            $msg = "1";
        } else {
            $msg = "0";
        }

        $customerField = ($customerId === null) ? "customer_id is NULL" : "customer_id = '".$customerId."'";
        if (!empty($customerId)) {
            $getCompareList = "select product_id from ".$tableName.
                              " where customer_id = ".$customerId.
                              " order by 'product_id' desc limit ".$compareCount;
        } else {
            $getCompareList = "select product_id from ".$tableName.
                              " where visitor_id='".$visitorId.
                              "' and ".$customerField;
        }

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
        $resultJson->setData(['compareList'=> $compareProducts,'msg'=> $msg]);
        return $resultJson;
    }
}
