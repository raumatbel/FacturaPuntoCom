define([
    'underscore',
    'Magento_Ui/js/grid/columns/select'
    ], function (_, Column) {
    'use strict';
    
    return Column.extend({
        defaults: {
        bodyTmpl: 'Facturacom_Facturacion/ui/grid/cells/text'
        },
        getOrderStatusColor: function (row) 
        {
            if (row.Status == 'enviada'){
                return 'enviada-status';
            } else if(row.Status == 'timbrada'){
                return 'timbrada-status';
            } else if(row.Status == 'eliminada'){
                return 'eliminada-status';
            } else if(row.Status == 'cancelada'){
                return 'cancelada-status';
            }
            return '#303030';
        },
        getLabel: function (row){
            return row.Status;
        }
    });
});