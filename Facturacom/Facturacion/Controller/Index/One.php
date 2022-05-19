<?php
namespace Facturacom\Facturacion\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use Magento\Store\Model\StoreManagerInterface;

use Facturacom\Facturacion\Helper\Factura;
use Facturacom\Facturacion\Helper\Cookie;

class One extends Action
{

    public function __construct(
        Context $context, 
        JsonFactory $jsonResultFactory,
        ScopeConfigInterface $scopeConfig, 
        ProductRepositoryInterface $productRepository,
        OrderFactory $orderFactory,
        Factura $facturaHelper,
        Cookie $cookieHelper,
        Item $taxItem,
        StoreManagerInterface $storeManager
        ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->orderFactory = $orderFactory;
        $this->facturaHelper = $facturaHelper;
        $this->cookieHelper = $cookieHelper;
        $this->taxItem = $taxItem;
        $this->storeManager = $storeManager;
        return parent::__construct($context);
    }

    public function execute()
    {
        
        if ($this->getRequest()->isXmlHttpRequest()) { // <--- Verifica que sea una petición del front (AJAX)
            
            $result = $this->jsonResultFactory->create();
            
            // Recuperamos los valores de la petición
            $rfc = $this->getRequest()->getPostValue('rfc');
            $orderNum = $this->getRequest()->getPostValue('order');
            $email = $this->getRequest()->getPostValue('email');
            
            if(is_null($rfc) || is_null($orderNum) || is_null($email))
            {
                $result->setData(['error' => 400, 'message' => 'No se enviarón los parametros']);
                return $result;
            }

            // Quitamos espacios en blanco (Para que sea mas fácil para el usuario)
            $rfc = trim($rfc);
            $orderNum = trim($orderNum);
            $email = trim($email);

            //Buscamos el pedido
            $order = $this->orderFactory->create()->loadByIncrementId($orderNum);

            if(!isset($order['entity_id'])){
                $result->setData(['error' => 400, 'message' => 'No existe un pedido con ese número. Por favor inténtelo con otro número.']);
                return $result;
            }
            
            //Validamos que el correo sea el mismo
            if($order['customer_email'] != $email){
                $result->setData(['error' => 400, 'message' => 'El email ingresado no coincide con el correo registrado en el pedido. Por favor inténtelo con otro correo.']);
                return $result;
            }
            
            // Validamos que el pedido esté pagado
            if(!in_array($order['status'], array('complete','processing'), true )){ 
                $result->setData(['error' => 400, 'message' => 'El pedido aún no se encuentra listo para facturar. Por favor espere a que su pedido sea enviado.']);
                return $result;
            } 
            
            // Validamos los dias de tolerancia
            if(!$this->validateDayOff($order['updated_at'])) { // <-- Validamos que los dias de tolerancia
                $result->setData(['error' => 400, 'message' => 'La fecha para facturar tu pedido ya pasó!']);
                return $result;
            } 

            $customer = $this->facturaHelper->getCustomerByRFC($rfc);



            // Debemos obtener la configuración de la tienda para ver si los impuestos vienen incluidos en el precio
            $preciosConImpuesto = $this->scopeConfig->getValue('tax/calculation/price_includes_tax', \Magento\Store\Model\ScopeInterface::SCOPE_STORE); 

            // Obtenemos la lista de impuestos aplicados a los items (Cada uno, incluyendo el cobro del envío)
            $tax_items = $this->taxItem->getTaxItemsByOrderId($order->getId()); 

            // Procedemos a ordenar la lista por item
            $taxes = [];
            foreach($tax_items as $tax){
                
                $item_id = $tax['item_id'];

                if($tax['taxable_item_type'] == "shipping"){
                    $item_id = "shipping";
                }

                if(isset($taxes[$item_id])){
                    $taxes[$item_id] []= $tax;
                } else {
                    $taxes[$item_id] = [$tax];
                }
            }

            $total_calculado = 0;
            $subtotal_calculado = 0;
            $descuento_calculado = 0;
            $impuestos_calculados = [];

            // Iteramos cada elemento del pedido
            $line_items = [];
            foreach ($order->getAllItems() as $item) {
                
                $product = $this->productRepository->getById($item['product_id']);

                if(!isset($product['clave_prod_serv']) || !isset($product['clave_unidad']) || !isset($product['texto_unidad'])){
                    $result->setData(['error' => 400, 'message' => 'Algún producto del pedido no tiene registrado las claves de producto y/o servicio.']);
                    return $result;
                }
                
                $newItem = [
                    'item_id'        => $item['item_id'],
                    'name'           => $item['name'],
                    'qty'            => intval($item['qty_ordered']),
                    'price'          => round($item['base_price'], 6),
                    'original_price' => round($item['base_original_price'], 6),
                    'discount'       => round(abs($item['discount_amount']), 6),
                    'claveProdServ'  => $product['clave_prod_serv'],
                    'claveUnidad'    => $product['clave_unidad'],
                    'unidad'         => $product['texto_unidad'],
                    'tax'            => 0,
                    'taxes'          => []
                ];

                if(isset($product['iva_especial'])){

                    $especial = [];
                    
                    if(strtoupper($product['iva_especial']) == 'EXENTO') {
                        $especial = ["code" => "IVA EXENTO", "tax_percent" => "0"];
                    } else if($product['iva_especial'] == 0){
                        $especial = ["code" => "IVA", "tax_percent" => "0"];
                    }

                    if(!empty($especial)){
                        if(isset($taxes[$item['item_id']])){
                            $taxes[$item['item_id']] []= $especial;
                        } else {
                            $taxes[$item['item_id']] = [$especial];
                        }
                    }
                }

                array_push($line_items, $newItem);
            }

            if($order['shipping_method'] && $order['shipping_amount'] > 0) {
                
                $newItem = [
                    'item_id'        => 'shipping',
                    'name'           => $order['shipping_description'],
                    'qty'            => 1,
                    'price'          => round($order['shipping_amount'], 6),
                    'original_price' => round($order['base_shipping_incl_tax'], 6),
                    'discount'       => round(abs($order['shipping_discount_amount']), 6),
                    'claveProdServ'  => '78102203',
                    'claveUnidad'    => 'E48',
                    'unidad'         => 'Unidad de Servicio',
                    'tax'            => 0,
                    'taxes'          => []
                ];
                array_push($line_items, $newItem);
            }

            // Volvemos a iterar los elementos para aplicar impuestos y obtener totales
            foreach($line_items as &$item){

                // Revisamos si el item tiene impuesto
                if(isset($taxes[$item['item_id']])){ 

                    $item_taxes = $taxes[$item['item_id']];

                    $iva = null;
                    $ieps = null;

                    foreach($item_taxes as $tax){

                        $code = strtoupper($tax['code']); // Procedemos a buscar la clase de impuesto asignada

                        if(is_null($ieps) && strpos($code, 'IEPS') !== false){
                            $ieps = $tax;
                        } else if(is_null($iva) && strpos($code, 'IVA') !== false){
                            $iva = $tax;
                        } else {
                            $result->setData(['error' => 400, 'message' => 'No se puede facturar el pedido ya que contiene impuestos no identificados o más de los necesarios']);
                            return $result;
                        }      
                    }

                    if($preciosConImpuesto){

                        // Primero le quitamos los impuestos al precio
                        $percent = 1;

                        if(!is_null($iva)){
                            $percent += ($iva['tax_percent'] / 100);
                        }
                        if(!is_null($ieps)){
                            $percent += ($ieps['tax_percent'] / 100);
                        }

                        $item['price'] = round($item['original_price'] / $percent, 6);
                    } 

                    $item['subtotal'] = round($item['price'] * $item['qty'], 6);

                    if($item['discount'] < $item['subtotal']){

                        $base = round($item['subtotal'] - $item['discount'], 6);
                        
                        if(!is_null($ieps)){
                            $percent = $ieps['tax_percent'] / 100;
                            $importe = round($base * $percent, 6);
                            $item['tax'] += $importe;
                            $item['taxes']['IEPS'] = ['base' => $base, 'percent' => floatval($ieps['tax_percent']), 'amount' => $importe];
                            $base += $importe;
                        }

                        if(!is_null($iva)){
                            $percent = $iva['tax_percent'] / 100;
                            $importe = round($base * $percent, 6);
                            $item['tax'] += $importe;

                            if(strpos($code, 'EXENTO') !== false){
                                $item['taxes']['IVA EXENTO'] = ['base' => $base, 'percent' => floatval($iva['tax_percent']), 'amount' => $importe];
                            } else {
                                $item['taxes']['IVA'] = ['base' => $base, 'percent' => floatval($iva['tax_percent']), 'amount' => $importe];
                            }
                        }
                        $item['total'] = $item['subtotal'] - $item['discount'] + $item['tax'];
                    } else {
                        $item['discount'] = $item['subtotal'];
                        $item['total'] = 0;
                    }
                } else {

                    $item['subtotal'] = $item['qty'] * $item['price'];
                    if($item['discount'] < $item['subtotal']){
                        $item['total'] = $item['subtotal'] - $item['discount'];
                    } else {
                        $item['total'] = 0;
                    }
                }

                $total_calculado += $item['total'];
                $subtotal_calculado += $item['subtotal'];

                //Ordenamos los impuestos
                foreach($item['taxes'] as $type => $tax){

                    $repetido = false;

                    foreach($impuestos_calculados as &$impuesto){
                        if($impuesto['type'] == $type && $impuesto['percent'] == $tax['percent']){
                            $impuesto['amount'] += $tax['amount'];
                            $repetido = true;
                            break;
                        }
                    }

                    if(!$repetido){
                        
                        if($type == 'IVA EXENTO' && ($tax['percent'] != 0 || $tax['amount'] != 0) ){
                            $result->setData(['error' => 400, 'message' => 'No se puede facturar el pedido ya que en algún item se registró IVA exento con un porcentaje diferente a 0']);
                            return $result;
                        }
                        
                        $impuestos_calculados[]= ['type' => $type, 'percent' => $tax['percent'], 'amount' => $tax['amount']];
                    }
                }
            }


            //Guardar información premilinarmente en cookies
            $pedido = [
                'id'                => $order['entity_id'], // Llave primaria de la tabla
                'order_number'      => $order['increment_id'], // Identificador del pedido
                'customer_email'    => $order['customer_email'], // Correo del cliente
                'total_tax'         => $order['tax_amount'], // Total de los impuestos
                'total_discount'    => abs($order['discount_amount']), // Descuento total
                'total'             => $order['grand_total'], // Total general
                'total_base'        => $order['base_subtotal'], // Subtotal
                'status'            => $order['status'], // Estado del pedido
                'payment_day'       => $order['updated_at'],
                'currency'          => $order->getOrderCurrencyCode(),
                'tipo_cambio'       => 1,
                'total_calculado' => $total_calculado,
                'subtotal_calculado' => $subtotal_calculado,
                'descuento_calculado' => $descuento_calculado,
                'impuestos_calculados' => $impuestos_calculados
            ];

            if($pedido['currency'] != 'MXN'){
                if($this->storeManager->getStore()->getBaseCurrencyCode() == $pedido['currency'] && $this->storeManager->getStore()->getCurrentCurrencyCode() == 'MXN'){
                    $pedido['tipo_cambio'] = $this->storeManager->getStore()->getCurrentCurrencyRate();
                } else {
                    $result->setData(['error' => 400, 'message' => 'No se puede facturar el pedido ya que no se puede obtener el tipo de cambio para la moneda '.$pedido['currency']]);
                    return $result;
                }
            }

            $this->cookieHelper->setCookie('order', json_encode($pedido));
            $this->cookieHelper->setCookie('customer', json_encode($customer));
            $this->cookieHelper->setCookie('line_items', json_encode($line_items));

            $result->setData([
                'error' => 200,
                'message' => 'Pedido encontrado exitósamente',
                'data' => [
                    'order' => $pedido,
                    'customer' => $customer,
                    'line_items' => $line_items,
                    'taxes_items' => $tax_items
                ]
            ]);

            return $result;

        }else{
            die('AJAX request only');
        }
    }

    private function validateDayOff($completed_date){

        $order_month = date("m",strtotime($completed_date));
        $current_month = date("m");
        $current_day = date("d");

        $daysoff = $this->scopeConfig->getValue('facturacom/facturacom_facturacion/daysoff', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $daysoff;

        if($order_month != $current_month){
            if($order_month < $current_month){
                if($current_day > $daysoff){
                    return false;
                }
            }
        }
        return true;
    }
}