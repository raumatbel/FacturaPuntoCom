<?php
namespace Facturacom\Facturacion\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;

use Facturacom\Facturacion\Helper\Factura;
use Facturacom\Facturacion\Helper\Cookie;

class Three extends Action
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

            $data = [
                'formaPago' =>  $this->getRequest()->getPostValue('payment_m'),
                'cuenta' => $this->getRequest()->getPostValue('num_cta_m'),
                'uso' => $this->getRequest()->getPostValue('uso'),
            ];

            if( $data["formaPago"] == null || $data["uso"] == null || (in_array($data["formaPago"], [3, 4, 28]) && $data["cuenta"] == null)){

                $result->setData([
                    'error' => 400,
                    'message' => 'No se han recibido todos los campos. Por favor revise la información del cliente.',
                    'data' => $data
                ]);
                return $result;
            }

            $data['order'] = json_decode($this->cookieHelper->getCookie('order'), true);
            $data['products'] = json_decode($this->cookieHelper->getCookie('line_items'), true);
            $data['customer'] = json_decode($this->cookieHelper->getCookie('customer'), true);
            
            $invoice = $this->facturaHelper->createInvoice($data);

            if (isset($invoice['response']) && $invoice['response'] == "success") {
                $result->setData([
                    'error' => 200,
                    'message' => 'Factura creada exitósamente',
                    'data' => $invoice
                ]);

                $this->cookieHelper->deleteCookie('order');
                $this->cookieHelper->deleteCookie('line_items');
                $this->cookieHelper->deleteCookie('customer');
                
            } else {
                $result->setData([
                    'error' => 400,
                    'message' => 'Ha ocurrido un error.',
                    'data' => $invoice,
                ]);
            }

            return $result;

        }else{
            die('AJAX request only');
        }
    }
}