<?php

namespace Facturacom\Facturacion\Model\Config\Source;

class ListMode implements \Magento\Framework\Data\OptionSourceInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => 'sandbox', 'label' => __('Sandbox (Modo de pruebas)')],
    ['value' => 'production', 'label' => __('Production')]
  ];
 }
}