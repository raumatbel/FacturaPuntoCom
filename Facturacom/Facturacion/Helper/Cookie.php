<?php
namespace Facturacom\Facturacion\Helper;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\APP\Helper\Context;

class Cookie extends AbstractHelper {

   protected $cookieManager;
   protected $cookieMetadataFactory;

   public function __construct(Context $context, CookieManagerInterface $cookieManager, CookieMetadataFactory $cookieMetadataFactory)
   {
        parent::__construct($context);
       $this->cookieManager = $cookieManager;
       $this->cookieMetadataFactory = $cookieMetadataFactory;
   }

   public function getCookie($cookieName) {
        return $this->cookieManager->getCookie($cookieName);
   }

   public function setCookie($cookieName, $value){

        $metadata = $this->cookieMetadataFactory
                         ->createSensitiveCookieMetadata() 
                         ->setPath('/');

        $this->cookieManager->setSensitiveCookie($cookieName, $value, $metadata); // La cookie está hecha para manejar datos sensibles
   }

   public function deleteCookie($cookieName)
   {
     if ($this->cookieManager->getCookie($cookieName)) {
          $metadata = $this->cookieMetadataFactory->createSensitiveCookieMetadata()->setPath('/');
          return $this->cookieManager->deleteCookie($cookieName, $metadata);
     }
   }
}