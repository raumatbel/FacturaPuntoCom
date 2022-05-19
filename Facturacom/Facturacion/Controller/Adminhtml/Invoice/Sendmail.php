<?php

namespace Facturacom\Facturacion\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action;

use Facturacom\Facturacion\Helper\Factura;

class Sendmail extends Action
{

    protected $_messageManager;

    public function __construct(Context $context, ManagerInterface $messageManager, Factura $helper, JsonFactory $jsonResultFactory) {
        parent::__construct($context);
        $this->_messageManager = $messageManager;
        $this->helper = $helper;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        if ($this->getRequest()->isXmlHttpRequest()) { // <--- Verifica que sea una petición de (AJAX)

            $result = $this->jsonResultFactory->create();

            $uid = $this->getRequest()->getParam('uid');

            if(is_null($uid))
            {
                $result->setData(['response' => 'error', 'message' => 'No se enviarón los parametros']);
                return $result;
            }

            $response = $this->helper->sendEmail($uid);
            $result->setData($response);;
            return $result;
            
        } else {
            die('AJAX request only');
        }
    }
}