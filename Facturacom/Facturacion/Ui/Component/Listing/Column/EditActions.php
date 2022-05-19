<?php

namespace Facturacom\Facturacion\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder;
use Magento\Framework\UrlInterface;

class EditActions extends Column {

    /** @var UrlBuilder */
    protected $actionUrlBuilder;

    /** @var UrlInterface */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilder $actionUrlBuilder,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->actionUrlBuilder = $actionUrlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {

            $storeId = $this->context->getFilterParam('store_id');

            foreach ($dataSource['data']['items'] as &$item) {

                // Formateamos el total
                $item['Total'] = "$ " . number_format(floatval($item['Total']), 2, '.', ','); 

                // Creamos los botones de descarga ()
                $pdf_url = $this->urlBuilder->getBaseUrl() . "facturacion/index/download?type=pdf&uid={$item['UID']}";
                $xml_url = $this->urlBuilder->getBaseUrl() . "facturacion/index/download?type=xml&uid={$item['UID']}";

                $item['download'] = html_entity_decode("
                    <a href='{$pdf_url}' target='_blank'><button class=\"btn\">PDF</button></a>
                    <a href='{$xml_url}' target='_blank'><button class=\"btn\" role=\"button\">XML</button></a>
                    ");

                $item['send_email'] = html_entity_decode("<button class=\"f-send-mail btn\" data-uid=\"{$item['UID']}\" >Enviar Correo</button>");
            
                if(in_array($item['Status'], ['enviada', 'timbrada'])){
                    $item['cancel'] = html_entity_decode("<button class=\"f-cancel-cfdi btn\" data-uid=\"{$item['UID']}\"  data-folio=\"{$item['Folio']}\">Cancelar</button>");
                } else {
                    $item['cancel'] = "";
                }
            }
        }

        return $dataSource;
    }
}