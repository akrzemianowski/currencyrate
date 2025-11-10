<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Adam Krzemianowski <adam.krzemianowski@gmail.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace CurrencyRate\Service;

use ArrayAccess;
use Configuration;
use Context;
use Currency;
use Product;

/**
 * Calculates and formats product prices in multiple currencies.
 */
final class ProductCurrencyDisplayService
{
    /**
     * @param CurrencyPriceCalculatorService $calculator
     */
    public function __construct(
        private CurrencyPriceCalculatorService $calculator
    ) {}

    /**
     * @param array|ArrayAccess $productData
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPricesForProduct(array|ArrayAccess $productData): array
    {
        if (empty($productData['id_product'])) {
            return [];
        }

        $productObj = new Product((int) $productData['id_product']);
        $basePrice = (float) $productObj->getPrice(true);

        if ($basePrice <= 0) {
            return [];
        }

        $providerCode = Configuration::get('CURRENCYRATE_PROVIDER_CODE') ?: 'nbp';
        $defaultCurrencyId = (int) Context::getContext()->currency->id;
        $baseCurrency = new Currency($defaultCurrencyId);

        return $this->calculator->calculateProductPrices(
            (int) $productData['id_product'],
            $basePrice,
            $baseCurrency->iso_code,
            $providerCode
        );
    }

    /**
     * @return string
     */
    public function getBaseCurrencyIso(): string
    {
        $defaultCurrencyId = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $baseCurrency = new Currency($defaultCurrencyId);

        return $baseCurrency->iso_code;
    }

    /**
     * @return string
     */
    public function getProviderCode(): string
    {
        return Configuration::get('CURRENCYRATE_PROVIDER_CODE') ?: 'nbp';
    }
}
