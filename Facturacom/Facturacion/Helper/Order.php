<?php

namespace Facturacom\Facturacion\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\APP\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Order extends AbstractHelper
{
    
    public function __construct(Context $context, OrderRepositoryInterface $orderRepository, ScopeConfigInterface $scopeConfig)
    {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
    }

    public function getOrderByNum($orderNum)
    {
        try {
            $order = $this->orderRepository->get($orderNum);
            return $order
            ;
            $orderData = array(
                'id'                => $order['entity_id'],
                'order_number'      => $order['increment_id'],
                'customer_email'    => $order['customer_email'],
                'total_tax'         => $order['tax_amount'],
                'total_discount'    => abs($order['discount_amount']),
                'total'             => $order['grand_total'],
                'total_base'        => $order['base_subtotal'],
                'status'            => $order['status'],
                'payment_day'       => $order['updated_at']
            );
    
            return (object) $orderData;

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }        
    }

    public function getOrderLines($order){
        
        $line_items = array();
        $order_items_collection = $order->getItemsCollection()
                                        ->addAttributeToSelect('*')
                                        ->addAttributeToFilter('product_type', array('eq'=>'simple'))
                                        ->load();

        // IEPS
        $iepsconfig =  $this->scopeConfig->getValue('facturacom/facturacom_facturacion/iepsconfig', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $iepscalc = $this->scopeConfig->getValue('facturacom/facturacom_facturacion/iepscalc', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        foreach ($order_items_collection as $order_item) {

            $item = Mage::getModel('sales/order_item')->load($order_item->getId())->getData();

            $itemId = $item['item_id'];

            if($item['parent_item_id']){
                $line_items[$item['parent_item_id']]['name'] = $item['name'];
            }else{
                $item_price = $this->getProductPrice($item);
                $_product = Mage::getModel('catalog/product')->load($item['product_id']);

                $line_row = array(
                    'id'            => $item['item_id'],
                    'product_id'    => $item['product_id'],
                    'name'          => $item['name'],
                    'qty'           => $item['qty_ordered'],
                    'base_price'    => $item_price['base_price'],
                    'price'         => $item_price['price'],
                    'tax_percent'   => $item['tax_percent'],
                    'discount'      => abs($item['discount_amount']),
                    'iepsconfig'    => $iepsconfig,
                    'iepscalc'      => $iepscalc,
                    'usaIeps'       => $_product->getData('usaIeps'),
                );
                $line_items[$itemId] = $line_row;
            }
        }

        $orderData = $order->getData();

        if($orderData['shipping_method']){

            $shipping_amount = ($orderData['shipping_amount'] > 0) ? $orderData['shipping_amount'] : 0.01;

            $shipping = array(
                'id'    => $orderData['shipping_method'],
                'name'  => $orderData['shipping_description'],
                'qty'   => 1,
                'base_price' => $shipping_amount,
                'price' => $shipping_amount,
                'discount' => 0,
                'shipping' => true,
            );
            array_push($line_items, $shipping);
        }

        $clean_collaction = array();
        foreach ($line_items as $item) {
            array_push($clean_collaction, $item);
        }
        return $clean_collaction;
    }
}