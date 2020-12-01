<?php

namespace RicardoMartins\PagSeguro\Plugin\Api;

class ProductRepositoryInterface
{
    public function beforeSave($subject, $product)
    {
        $product->setRmInterestOptions("");
        $product->setRmPagSeguroLastUpdate(0);
    }
}
