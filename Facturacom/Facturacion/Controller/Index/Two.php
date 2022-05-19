<?php
namespace Facturacom\Facturacion\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;

use Facturacom\Facturacion\Helper\Factura;
use Facturacom\Facturacion\Helper\Cookie;

class Two extends Action
{

    public function __construct(
        Context $context, 
        JsonFactory $jsonResultFactory,
        Factura $facturaHelper,
        Cookie $cookieHelper
        ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->facturaHelper = $facturaHelper;
        $this->cookieHelper = $cookieHelper;
        return parent::__construct($context);
    }

    public function execute()
    {
        if ($this->getRequest()->isXmlHttpRequest()) { // <--- Verifica que sea una petición del front (AJAX)
            
            $result = $this->jsonResultFactory->create();

            $customerData = array(
                'uid'         => $this->getRequest()->getPostValue('uid'),
                'method'      => $this->getRequest()->getPostValue('api_method'),
                'g_nombre'    => $this->getRequest()->getPostValue('g_nombre'),
                'g_apellidos' => $this->getRequest()->getPostValue('g_apellidos'),
                'g_email'     => $this->getRequest()->getPostValue('g_email'),
                'f_telefono'  => $this->getRequest()->getPostValue('f_telefono'),
                'f_nombre'    => $this->getRequest()->getPostValue('f_nombre'),
                'f_rfc'       => $this->getRequest()->getPostValue('f_rfc'),
                'f_regimen'   => $this->getRequest()->getPostValue('f_regimen'),
                'f_calle'     => $this->getRequest()->getPostValue('f_calle'),
                'f_exterior'  => $this->getRequest()->getPostValue('f_exterior'),
                'f_interior'  => $this->getRequest()->getPostValue('f_interior'),
                'f_colonia'   => $this->getRequest()->getPostValue('f_colonia'),
                'f_municipio' => $this->getRequest()->getPostValue('f_municipio'),
                'f_estado'    => $this->getRequest()->getPostValue('f_estado'),
                'f_pais'      => $this->getRequest()->getPostValue('f_pais'),
                'f_numregidtrib' => $this->getRequest()->getPostValue('f_numregidtrib'),
                'f_cp'        => $this->getRequest()->getPostValue('f_cp'),
            );

            if( $customerData["g_nombre"] == null || $customerData["g_apellidos"] == null ||
                $customerData["g_email"] == null || $customerData["f_calle"] == null ||
                $customerData["f_colonia"] == null || $customerData["f_cp"] == null ||
                $customerData["f_estado"] == null || $customerData["f_exterior"] == null ||
                $customerData["f_municipio"] == null || $customerData["f_nombre"] == null ||
                $customerData["f_rfc"] == null || $customerData["f_telefono"] == null || $customerData["f_regimen"] == null){

                $result->setData([
                    'error' => 400,
                    'message' => 'No se han recibido todos los campos. Por favor revise la información del cliente.',
                    'data' => $customerData
                ]);
                return $result;
            }

            $customerNewData = $this->facturaHelper->createCustomer($customerData);

            //Get information saved previously
            $order     = $this->cookieHelper->getCookie('order');
            $lineItems = $this->cookieHelper->getCookie('line_items');
            $this->cookieHelper->setCookie('customer', json_encode($customerNewData)); //Updating customer info

            $result->setData([
                'error' => 200,
                'message' => 'Cliente creado/actualizado exitósamente',
                'data' => array(
                    'order' => json_decode($order, true),
                    'customer' => $customerNewData,
                    'line_items' => json_decode($lineItems, true)
                )
            ]);

            return $result;

        }else{
            die('AJAX request only');
        }
    }
}