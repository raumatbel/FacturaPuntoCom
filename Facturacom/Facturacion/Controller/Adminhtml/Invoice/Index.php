<?php

namespace Facturacom\Facturacion\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\UrlInterface;
use Magento\Backend\App\Action;

class Index extends Action
{
	protected $resultPageFactory = false;

	public function __construct(Context $context, PageFactory $resultPageFactory, UrlInterface $urlBuilder)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
		$this->urlBuilder = $urlBuilder;
	}
	public function execute()
	{
    	$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Mis facturas')));
		return $resultPage;
	}
}