<?php

namespace App\Services\PriceChecking;

use App\Models\Item;

interface PriceCheckingServiceInterface
{
    /**
     * Check prices for a given item
     *
     * @param Item $item
     * @return array Array of price data
     */
    public function checkPrices(Item $item): array;

    /**
     * Get the source name for this service
     *
     * @return string
     */
    public function getSourceName(): string;

    /**
     * Check if this service can handle the given item
     *
     * @param Item $item
     * @return bool
     */
    public function canHandle(Item $item): bool;
}