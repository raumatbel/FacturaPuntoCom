<?php

namespace Facturacom\Facturacion\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action;

use Facturacom\Facturacion\Helper\Factura;

class Cancel extends Action
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

            $uid = $this->getRequest()->getPostValue('uid');
            $motivo = $this->getRequest()->getPostValue('motivo');
            $folioSustituto = $this->getRequest()->getPostValue('folioSustituto');

            $response = $this->helper->cancel($uid, $motivo, $folioSustituto);
            $result->setData($response);;
            return $result;
            
        } else {
            die('AJAX request only');
        }
    }
}