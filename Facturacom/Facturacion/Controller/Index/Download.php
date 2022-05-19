<?php

namespace Facturacom\Facturacion\Controller\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Action\Action;

use Facturacom\Facturacion\Helper\Factura;

class Download extends Action
{
	protected $resultRawFactory;

	public function __construct(Context $context, RawFactory $resultRawFactory, Factura $helper)
	{
		parent::__construct($context);
		$this->resultRawFactory = $resultRawFactory;
        $this->helper = $helper;
	}

	public function execute()
	{
        $uid = $this->getRequest()->getParam('uid');
        $type = $this->getRequest()->getParam('type');

        $file = $this->helper->downloadFile($type, $uid);

        $resultRaw = $this->resultRawFactory->create();

        $resultRaw->setContents($file); //set content for download file here
        
        if($type == 'pdf'){
            header("Content-type:application/pdf");
            header('Content-Disposition: attachment; filename="'.$uid.'.pdf"');
        } else {
            header("Content-type:application/xml");
            header('Content-Disposition: attachment; filename="'.$uid.'.xml"');
        }

		return $resultRaw;
	}


}