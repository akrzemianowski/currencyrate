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

use Cache;
use Configuration;
use Context;
use Currency;
use CurrencyRate\Repository\CurrencyRateRepository;

final class CurrencyPriceCalculatorService
{
    private const CACHE_PREFIX = 'currencyrate_prices_';
    private const CACHE_TTL = 3600;
    private const RATES_CACHE_PREFIX = 'currencyrate_rates_';
    private const RATES_CACHE_TTL = 3600;

    private CurrencyRateRepository $repository;
    private Context $context;

    public function __construct(CurrencyRateRepository $repository)
    {
        $this->repository = $repository;
        $this->context = Context::getContext();
    }

    public function calculateProductPrices(
        int $productId,
        float $basePrice,
        string $baseCurrencyIso,
        string $providerCode
    ): array {
        $cacheKey = $this->buildCacheKey($productId, $baseCurrencyIso, $providerCode);
        $cache = Cache::getInstance();
        if ($cache) {
            $cachedPrices = $cache->get($cacheKey);
            if ($cachedPrices !== false) {
                return $cachedPrices;
            }
        }

        $currencies = $this->getActiveCurrencies();
        if (count($currencies) <= 1) {
            return [];
        }

        $rates = $this->getExchangeRates($baseCurrencyIso, $providerCode);
        if (empty($rates)) {
            return [];
        }

        $currencyPrices = $this->calculatePricesForCurrencies(
            $currencies,
            $basePrice,
            $baseCurrencyIso,
            $rates
        );

        if (!empty($currencyPrices) && $cache) {
            $cache->set($cacheKey, $currencyPrices, self::CACHE_TTL);
        }

        return $currencyPrices;
    }

    private function getExchangeRates(string $baseIso, string $providerCode): array
    {
        $cacheKey = self::RATES_CACHE_PREFIX . $baseIso . '_' . $providerCode;
        $cache = Cache::getInstance();
        if ($cache) {
            $cachedRates = $cache->get($cacheKey);
            if ($cachedRates !== false) {
                return $cachedRates;
            }
        }

        $rates = $this->repository->getAllLatestRates($baseIso, $providerCode);

        if (!empty($rates) && $cache) {
            $cache->set($cacheKey, $rates, self::RATES_CACHE_TTL);
        }

        return $rates;
    }

    private function getActiveCurrencies(): array
    {
        return Currency::getCurrencies(false, true);
    }

    private function calculatePricesForCurrencies(
        array $currencies,
        float $basePrice,
        string $baseCurrencyIso,
        array $rates
    ): array {
        $currencyPrices = [];

        foreach ($currencies as $currency) {
            $currencyIso = $currency['iso_code'];

            if ($currencyIso === $baseCurrencyIso) {
                $currencyPrices[] = $this->buildCurrencyPriceData(
                    $currency,
                    $basePrice,
                    $basePrice,
                    1.0,
                    true
                );
                continue;
            }

            if (!isset($rates[$currencyIso])) {
                continue;
            }

            $rate = $rates[$currencyIso];
            $convertedPrice = $this->convertPrice($basePrice, $rate);

            $currencyPrices[] = $this->buildCurrencyPriceData(
                $currency,
                $convertedPrice,
                $basePrice,
                $rate,
                false
            );
        }

        return $currencyPrices;
    }

    private function convertPrice(float $basePrice, float $rate): float
    {
        if ($rate <= 0) {
            return 0.0;
        }

        return $basePrice / $rate;
    }

    private function buildCurrencyPriceData(
        array $currency,
        float $price,
        float $basePrice,
        float $rate,
        bool $isBase
    ): array {
        return [
            'iso_code' => $currency['iso_code'],
            'name' => $currency['name'],
            'sign' => $currency['sign'],
            'price' => $price,
            'price_formatted' => $this->context->currentLocale->formatPrice($price, $currency['iso_code']),
            'is_base' => $isBase,
            'rate' => $rate,
            'base_price' => $basePrice,
        ];
    }

    private function buildCacheKey(int $productId, string $baseCurrencyIso, string $providerCode): string
    {
        return self::CACHE_PREFIX . $productId . '_' . $baseCurrencyIso . '_' . $providerCode;
    }

    public function clearProductCache(int $productId): void
    {
        $baseCurrencyId = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $baseCurrency = new Currency($baseCurrencyId);
        $providerCode = Configuration::get('CURRENCYRATE_PROVIDER_CODE') ?: 'nbp';

        $cacheKey = $this->buildCacheKey($productId, $baseCurrency->iso_code, $providerCode);
        $cache = Cache::getInstance();
        if ($cache) {
            $cache->delete($cacheKey);
        }
    }

    public function clearRatesCache(): void
    {
        $currencies = $this->getActiveCurrencies();
        $providerCode = Configuration::get('CURRENCYRATE_PROVIDER_CODE') ?: 'nbp';

        $cache = Cache::getInstance();
        if ($cache) {
            foreach ($currencies as $currency) {
                $cacheKey = self::RATES_CACHE_PREFIX . $currency['iso_code'] . '_' . $providerCode;
                $cache->delete($cacheKey);
            }
        }
    }

    public function clearAllCache(): void
    {
        $this->clearRatesCache();
    }
}
