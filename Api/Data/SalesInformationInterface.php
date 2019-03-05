<?php

namespace Lev\SalesInformation\Api\Data;

/**
 * Interface SalesInformationInterface
 * @package Lev\SalesInformation\Api\Data
 */
interface SalesInformationInterface
{
    /**
     * Get qty of orders with this product
     *
     * @return int
     */
    public function getQty(): int;

    /**
     * Get last order with this product
     *
     * @return string
     */
    public function getLastOrder(): string;
}
