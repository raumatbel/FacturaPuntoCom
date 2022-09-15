<?php

namespace Facturacom\Facturacion\Ui\DataProvider\Invoice;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Facturacom\Facturacion\Helper\Factura;

class DataProvider extends AbstractDataProvider
{

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        $name,
        $primaryFieldName,
        $requestFieldName,
        Factura $helper,
        Http $request,
        array $meta = [],
        array $data = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getCollection()
    {
        $paging = $this->request->getParam('paging');
        $filters = $this->request->getParam('filters');
        $sorting = $this->request->getParam('sorting');

        if(!is_null($paging)){
            $pagesize = intval($paging['pageSize']);
            $pageCurrent = intval($paging['current']);
        } else {
            $pagesize = 20;
            $pageCurrent = 1;
        }
        
        if(!is_null($filters)){
            if(isset($filters['NumOrder'])){
                $filters['num_order'] = $filters['NumOrder'];
            }
            if(isset($filters['Receptor'])){
                $filters['rfc'] = $filters['Receptor'];
            }
        }

        $data = $this->helper->getInvoices($pagesize, $pageCurrent, $filters);

        if(is_null($data)){ // Si hay algun error
            $data = ['totalRecords' => 0, 'items' => []];
        }

        if(!is_null($sorting)){

            $field = $sorting['field'];
            $direction = $sorting['direction'];

            if(!in_array($field, ['download', 'send_email', 'cancel'])){ // Estos campos son creados, no se usan para filtrar
                usort($data['data'], function($a, $b) use ($field, $direction){
                    if($direction === 'asc'){
                        return $a[$field] <=> $b[$field];
                    }
                    return $b[$field] <=> $a[$field];
                });
            }
        }

        return [
            'totalRecords' => $data['total'],
            'items'        => $data['data'],
        ];
    }

    public function getData()
    {
        if (!$this->getCollection()) {
            $this->getCollection();
        }
        return $this->getCollection();
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        $this->searchCriteriaBuilder->addFilter($filter);
    }

    public function addOrder($field, $direction)
    {
        $this->searchCriteriaBuilder->addSortOrder($field, $direction);
    }

    public function setLimit($offset, $size)
    {
        $this->searchCriteriaBuilder->setPageSize($size);
        $this->searchCriteriaBuilder->setCurrentPage($offset);
    }
}
