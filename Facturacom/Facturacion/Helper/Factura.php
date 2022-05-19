<?php
/**
 * Helper de Factura.com
 * 
 * Catalogo
 * -> getInvoices
 */

namespace Facturacom\Facturacion\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\APP\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Factura extends AbstractHelper
{
    private $url;
    private $apikey;
    private $apisecret;

    public function __construct(Context $context, ScopeConfigInterface $scopeConfig)
    {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;

        if($this->scopeConfig->getValue('facturacom/facturacom_general/mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 'production'){
            $this->url = "https://factura.com";
        } else {
            $this->url = $this->scopeConfig->getValue('facturacom/facturacom_general/sandbox_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        $this->apikey = $this->scopeConfig->getValue('facturacom/facturacom_general/apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->apisecret = $this->scopeConfig->getValue('facturacom/facturacom_general/apisecret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }


    public function getInvoices($page_size, $current_page, $filters){
        $endpoint = '/api/v3/cfdi40/list?=&type_document=factura&per_page='. $page_size. '&page=' . $current_page ;
        return $this->apiCall('GET', $endpoint, $filters);
    }

    public function downloadFile($type = 'pdf', $uid){
        $endpoint = "/api/v3/cfdi40/{$uid}/{$type}";
        return $this->apiCall('GET', $endpoint, null, null, true);
    }

    public function sendEmail($uid){
        $endpoint = "/api/v3/cfdi40/{$uid}/email";
        return $this->apiCall('GET', $endpoint);
    }

    public function getCustomerByRFC($customerRfc){
        $endpoint = "/api/v1/clients/{$customerRfc}";
        $result = $this->apiCall('GET', $endpoint);
        if($result['status'] == 'success' && $result['Data']['Regimen'] != null){
            foreach($this->getRegimenes() as $key => $value){
                if($value == $result['Data']['Regimen']){
                    $result['Data']['RegimenClave'] = $key;
                    break;
                }
            }
        }
        return $result;
    }

    public function getCountries(){
        $endpoint = '/api/v3/catalogo/Pais';
        $result = $this->apiCall('GET', $endpoint);

        $countries = array();
        if ($result['data']) {

            foreach ($result['data'] as $value) {
                $countries[$value['key']] = $value['name'];
            }
        }

        return $countries;
    }

    public function getRegimenes(){
        return [
            '601' => 'General de Ley Personas Morales',
            '603' => 'Personas Morales con Fines no Lucrativos',
            '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
            '606' => 'Arrendamiento',
            '607' => 'Régimen de Enajenación o Adquisición de Bienes',
            '608' => 'Demás ingresos',
            '610' => 'Residentes en el Extranjero sin Establecimiento Permanente en México',
            '611' => 'Ingresos por Dividendos (socios y accionistas)',
            '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
            '614' => 'Ingresos por intereses',
            '615' => 'Régimen de los ingresos por obtención de premios',
            '616' => 'Sin obligaciones fiscales',
            '620' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
            '621' => 'Incorporación Fiscal',
            '622' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
            '623' => 'Opcional para Grupos de Sociedades',
            '624' => 'Coordinados',
            '625' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
            '626' => 'Régimen Simplificado de Confianza'
        ];
    }

    public function createCustomer($data){

        if($data['method'] == 'create'){
            $endpoint = '/api/v1/clients/create';
        }else{
            $endpoint = '/api/v1/clients/' . $data['uid'] . '/update';
        }
        $params = array(
            'nombre'          => $data['g_nombre'],
            'apellidos'       => $data['g_apellidos'],
            'email'           => $data['g_email'],
            'telefono'        => $data['f_telefono'],
            'razons'          => $data['f_nombre'],
            'rfc'             => $data['f_rfc'],
            'regimen'         => $data['f_regimen'],
            'calle'           => $data['f_calle'],
            'numero_exterior' => $data['f_exterior'],
            'numero_interior' => $data['f_interior'],
            'codpos'          => $data['f_cp'],
            'colonia'         => $data['f_colonia'],
            'estado'          => $data['f_estado'],
            'ciudad'          => $data['f_municipio'],
            'delegacion'      => $data['f_municipio'],
            'pais'            => $data['f_pais'],
            'save'            => true,
        );

        if ($data['f_pais'] != 'MEX' && isset($data['f_numregidtrib'])) {
            $params['numregidtrib'] = $data['f_numregidtrib'];
        }

        return $this->apiCall('POST', $endpoint, $params);
    }

    public function getMetodosDePago(){
        return [
            "01" => "Efectivo",
            "02" => "Cheque nominativo",
            "03" => "Transferencia electrónica de fondos",
            "04" => "Tarjeta de crédito",
            "05" => "Monedero electrónico",
            "06" => "Dinero electrónico",
            "08" => "Vales de despensa",
            "12" => "Dación en pago",
            "13" => "Pago por subrogación",
            "14" => "Pago por consignación",
            "15" => "Condonación",
            "17" => "Compensación",
            "23" => "Novación",
            "24" => "Confusión",
            "25" => "Remisión de deuda",
            "26" => "Prescripción o caducidad",
            "27" => "A satisfacción del acreedor",
            "28" => "Tarjeta de débito",
            "29" => "Tarjeta de servicios",
            "30" => "Aplicación de anticipos",
            "31" => "Intermediario pagos",
            "99" => "Por definir"
        ];
    }

    public function getUsosDeCfdi(){
        return [
            "G01" => "Adquisición de mercancias",
            "G02" => "Devoluciones, descuentos o bonificaciones",
            "G03" => "Gastos en general",
            "I01" => "Construcciones",
            "I02" => "Mobilario y equipo de oficina por inversiones",
            "I03" => "Equipo de transporte",
            "I04" => "Equipo de computo y accesorios",
            "I05" => "Dados, troqueles, moldes, matrices y herramental",
            "I06" => "Comunicaciones telefónicas",
            "I07" => "Comunicaciones satelitales",
            "I08" => "Otra maquinaria y equipo",
            "D01" => "Honorarios médicos, dentales y gastos hospitalarios",
            "D02" => "Gastos médicos por incapacidad o discapacidad",
            "D03" => "Gastos funerales",
            "D04" => "Donativos",
            "D05" => "Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación)",
            "D06" => "Aportaciones voluntarias al SAR",
            "D07" => "Primas por seguros de gastos médicos",
            "D08" => "Gastos de transportación escolar obligatoria",
            "D09" => "Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones",
            "D10" => "Pagos por servicios educativos (colegiaturas)",
            "S01" => "Sin efectos fiscales"
        ];
    }

    public function createInvoice($data)
    {
        $endpoint = '/api/v3/cfdi40/create';

        $conceptos = [];

        foreach($data['products'] as $product){

            $concepto = [
                'Descuento' => round($product['discount'], 6),
                'ValorUnitario' => round($product['price'], 6),
                'Cantidad' => $product['qty'],
                'ClaveProdServ' => $product['claveProdServ'],
                'ClaveUnidad' => $product['claveUnidad'],
                'Unidad' => $product['unidad'],
                'Descripcion' => $this->limit_text($product['name'], 100)
            ];
            
            $concepto['Impuestos']['Traslados'] = [];

            foreach($product['taxes'] as $type => $tax){

                $concepto['Impuestos']['Traslados'] []=
                [
                    "Base" => $tax['base'],
                    "Impuesto" => $type == 'IEPS' ? "003" : "002",
                    "TipoFactor" => $type == 'IVA EXENTO' ? 'Exento' : 'Tasa',
                    "TasaOCuota" => $type == 'IVA EXENTO' ? "0" : ($tax['percent'] / 100),
                    "Importe" => $type == 'IVA EXENTO' ? "0" : $tax['amount']
                ];
            }
            
            $conceptos[]= $concepto;
        }

        $params = [
            "Receptor" => ["UID" => $data['customer']['Data']['UID']],
            "TipoDocumento" => "factura",
            "Conceptos" => $conceptos,
            "UsoCFDI" => $data['uso'],
            "Serie" => $this->scopeConfig->getValue('facturacom/facturacom_facturacion/serie', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            "Cuenta" => $data['cuenta'],
            "Redondeo" => 2,
            "FormaPago" => $data['formaPago'],
            "MetodoPago" => "PUE",
            "Moneda" => "MXN",
            "NumOrder" => $data['order']['order_number'],
            "EnviarCorreo" => $this->scopeConfig->getValue('facturacom/facturacom_formulario/enviarEmail', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];

        return $this->apiCall('POST', $endpoint, $params);
    }

    public function getAccountDetails(){
        $endpoint = '/api/v1/current/account';
        return $this->apiCall('GET', $endpoint);
    }

    public function cancel($uid, $motivo, $folioSustituto){
        $endpoint = "/api/v3/cfdi40/{$uid}/cancel";
        $params = ['motivo' => $motivo, 'folioSustituto' => $folioSustituto];
        return $this->apiCall('POST', $endpoint, $params);
    }

    private function apiCall($verb, $endpoint, $params = null, $debug = null, $raw = false){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url . $endpoint );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);

        $headers = array(
            'Content-Type: application/json',
            'F-PLUGIN: c963d66bb5ff4b1eb3927744825e820a1f7fd6d6',
            'F-API-KEY:' . $this->apikey,
            'F-SECRET-KEY:' . $this->apisecret
        );

        if(!is_null($params)){
            $dataString = json_encode($params);

            $httpheader = array('Content-Length:' . strlen($dataString));

            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);

            if($debug == true){
                echo "<pre>";
                print_r($dataString);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        try{
            $data = curl_exec($ch);
            if(curl_error($ch)){
                return 'error:' . curl_error($ch);
            }
            curl_close($ch);
        }catch(Exception $e){
            return $e;
            print('Exception occured: ' . $e->getMessage());die;
        }

        if($raw){
            return $data;
        }
        
        return json_decode($data, true);
    }

    private function limit_text($text, $limit) {
        if (str_word_count($text, 0) > $limit) {
            $words = str_word_count($text, 2);
            $pos = array_keys($words);
            $text = substr($text, 0, $pos[$limit]) . '...';
        }

        return str_replace(array("\r", "\n"), ' ', $text);
    }
}