<?php

namespace Facturacom\Facturacion\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;

use Facturacom\Facturacion\Helper\Factura;

class Form extends Template
{
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, Factura $helper, array $data = []){
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    public function getAccountDetails(){

        $result = $this->helper->getAccountDetails();
        
        if($result['status'] == 'success'){
            return [
                'razon_social' => $result['data']['razon_social'],
                'rfc' => $result['data']['rfc'],
                'regimen' => $result['data']['regimen_fiscal'],
                'calle' => $result['data']['calle'],
                'exterior' => $result['data']['exterior'],
                'interior' => $result['data']['interior'],
                'colonia' => $result['data']['colonia'],
                'codpos' => $result['data']['codpos'],
                'ciudad' => $result['data']['ciudad'],
                'estado' => $result['data']['estado']
            ];
        }

        return [
            'razon_social' => '',
            'rfc' => '',
            'regimen' => '',
            'calle' => '',
            'exterior' => '',
            'interior' => '',
            'colonia' => '',
            'codpos' => '',
            'ciudad' => '',
            'estado' => ''
        ];
    }

    public function getCountries(){
        return $this->helper->getCountries();
    }
    public function getRegimenes(){
        return $this->helper->getRegimenes();
    }
    public function getMetodosDePago(){
        return $this->helper->getMetodosDePago();
    }
    public function getUsosDeCfdi(){
        return $this->helper->getUsosDeCfdi();
    }
    public function getModuleTitle(){
        return $this->scopeConfig->getValue('facturacom/facturacom_formulario/titulo', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getModuleDescription(){
        return $this->scopeConfig->getValue('facturacom/facturacom_formulario/descripcion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getModuleHeadColor(){
        return $this->scopeConfig->getValue('facturacom/facturacom_formulario/color_fondo', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getModuleHeadFontColor(){
        return $this->scopeConfig->getValue('facturacom/facturacom_formulario/color_fuente', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}