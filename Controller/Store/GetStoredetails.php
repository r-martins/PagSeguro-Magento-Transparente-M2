<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Controller\Store;

use Magento\Framework\Controller\ResultFactory;
//use RicardoMartins\PagSeguro\Helper\Data;

use Magento\Customer\Model\Address\Config;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\DataObject;

class GetStoredetails extends \Magento\Framework\App\Action\Action
{    
     /**
     * PagSeguro Helper
     *
     * @var RicardoMartins\PagSeguro\Helper\Data;
     */ 
    

   protected $_storeManager;    


    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var RegionFactory
     */
    private $regionFactory;
    private $countryInformation;
    private $scopeConfig;
    /**
     * @var Config
     */
    private $addressConfig;
    public function __construct( 
        \Magento\Store\Model\StoreManagerInterface $storeManager, 
\Magento\Framework\App\Action\Context $context,
\Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig

    )
    {        
      
        $this->_storeManager = $storeManager;   
    
	$this->countryInformation = $countryInformation;
	$this->scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    /**
     * Retrieve formatted store address from config
     *
     * @param Store $store
     * @return string
     */
    public function getFormattedAddress(Store $store)
    {
        /** @var \Magento\Customer\Block\Address\Renderer\DefaultRenderer $renderer */
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($this->getStoreInformationObject($store)->getData());
    }
    
    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
    
    /**
     * Get website identifier
     *
     * @return string|int|null
     */
    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }
    
    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }
    
    /**
     * Get Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
    
    /**
     * Get current url for store
     *
     * @param bool|string $fromStore Include/Exclude from_store parameter from URL
     * @return string     
     */
    public function getStoreUrl($fromStore = true)
    {
        return $this->_storeManager->getStore()->getCurrentUrl($fromStore);
    }
    
    /**
     * Check if store is active
     *
     * @return boolean
     */
    public function isStoreActive()
    {
        return $this->_storeManager->getStore()->isActive();
    }

   public function execute()
    {
         $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);     
        try{
        	$storeid = $this->getStoreId();
		$websiteid = $this->getWebsiteId();
		$storecode = $this->getStoreCode();
		$storename = $this->getStoreName();
		$storeurl = $this->getStoreUrl();

//$conf = $this->_helper->getStoreConfig('app/code/RicardoMartins/PagSeguro/etc/frontend/di.xml');

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');

$rootPath  =  $directory->getRoot();

$compojson = file_get_contents($rootPath.'/app/code/RicardoMartins/PagSeguro/composer.json');

$config_data= json_decode($compojson,true);
 unset($config_data['autoload']);

		$street_line1 = $this->scopeConfig->getValue('general/store_information/street_line1');
		$street_line2 = $this->scopeConfig->getValue('general/store_information/street_line2');
		$postcode = $this->scopeConfig->getValue('general/store_information/postcode');
		$city = $this->scopeConfig->getValue('general/store_information/city');
		$phone = $this->scopeConfig->getValue('general/store_information/phone');
		$merchant_vat_number = $this->scopeConfig->getValue('general/store_information/merchant_vat_number');
		$countryid = $this->scopeConfig->getValue('general/store_information/country_id');

		$country = $this->countryInformation->getCountryInfo($countryid);
		$countryname = $country->getFullNameLocale();

             $result = array(
                'status'=> 'success',
		'config'=>$config_data,
                'storeid' => $storeid,
		'websiteid' => $websiteid,
		'storecode' => $storecode,
		'storename' => $storename,
		'storeurl' => $storeurl,
		'street_line1'=>$street_line1,
		'street_line2'=>$street_line2,
		'postcode'=>$postcode,
		'city'=>$city,
		'merchant_vat_number'=>$merchant_vat_number,
		'phone'=>$phone,
		'countryname'=>$countryname
            );


         }catch (\Exception $e) {
            $result = array('status'=> 'error','message' => $e->getMessage());
        }

        $resultJson->setData($result);         
        return $resultJson;
        
    }
}
