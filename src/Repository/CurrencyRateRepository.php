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

namespace CurrencyRate\Repository;

use Db;
use CurrencyRate\DTO\CurrencyRatePoint;

/**
 * Handles database operations for currency rates.
 */
final class CurrencyRateRepository
{
    /**
     * @param string $baseIso
     * @param string $quoteIso
     * @param string $date
     * @param string $providerCode
     *
     * @return int|null
     */
    private function findByUniqueKey(string $baseIso, string $quoteIso, string $date, string $providerCode): ?int
    {
        $db = \Db::getInstance();

        $existingId = $db->getValue(
            'SELECT id_currency_rate FROM ' . _DB_PREFIX_ . 'currency_rate
            WHERE base_iso = "' . pSQL($baseIso) . '"
            AND quote_iso = "' . pSQL($quoteIso) . '"
            AND date = "' . pSQL($date) . '"
            AND provider = "' . pSQL($providerCode) . '"'
        );

        return $existingId ? (int) $existingId : null;
    }

    /**
     * @param CurrencyRatePoint $point
     *
     * @return int
     */
    private function insert(CurrencyRatePoint $point): int
    {
        $db = \Db::getInstance();
        $now = date('Y-m-d H:i:s');

        $db->insert(
            'currency_rate',
            [
                'date' => pSQL($point->date->format('Y-m-d')),
                'base_iso' => pSQL($point->baseIso),
                'quote_iso' => pSQL($point->quoteIso),
                'provider' => pSQL($point->providerCode),
                'rate' => (float) $point->rate,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) $db->Insert_ID();
    }

    /**
     * @param int $id
     * @param float $rate
     */
    private function update(int $id, float $rate): void
    {
        $db = \Db::getInstance();
        $now = date('Y-m-d H:i:s');

        $db->update(
            'currency_rate',
            [
                'rate' => (float) $rate,
                'updated_at' => $now,
            ],
            'id_currency_rate = ' . (int) $id
        );
    }

    /**
     * Inserts or updates a currency rate.
     *
     * @param CurrencyRatePoint $p
     */
    public function upsert(CurrencyRatePoint $p): void
    {
        $existingId = $this->findByUniqueKey(
            $p->baseIso,
            $p->quoteIso,
            $p->date->format('Y-m-d'),
            $p->providerCode
        );

        if ($existingId !== null) {
            $this->update($existingId, $p->rate);
        } else {
            $this->insert($p);
        }
    }

    /**
     * @param string $baseIso
     * @param string $quoteIso
     * @param string $providerCode
     *
     * @return float|null
     */
    public function getLatestRate(string $baseIso, string $quoteIso, string $providerCode): ?float
    {
        $db = \Db::getInstance();

        $rate = $db->getValue(
            'SELECT rate FROM ' . _DB_PREFIX_ . 'currency_rate
            WHERE base_iso = "' . pSQL($baseIso) . '"
            AND quote_iso = "' . pSQL($quoteIso) . '"
            AND provider = "' . pSQL($providerCode) . '"
            ORDER BY date DESC, updated_at DESC
            LIMIT 1'
        );

        return $rate !== false ? (float) $rate : null;
    }

    /**
     * Gets most recent rates for all quote currencies.
     *
     * @param string $baseIso
     * @param string $providerCode
     *
     * @return array<string, float>
     */
    public function getAllLatestRates(string $baseIso, string $providerCode): array
    {
        $db = \Db::getInstance();

        $sql = 'SELECT cr1.quote_iso, cr1.rate
                FROM ' . _DB_PREFIX_ . 'currency_rate cr1
                INNER JOIN (
                    SELECT quote_iso, MAX(date) as max_date
                    FROM ' . _DB_PREFIX_ . 'currency_rate
                    WHERE base_iso = "' . pSQL($baseIso) . '"
                    AND provider = "' . pSQL($providerCode) . '"
                    GROUP BY quote_iso
                ) cr2 ON cr1.quote_iso = cr2.quote_iso AND cr1.date = cr2.max_date
                WHERE cr1.base_iso = "' . pSQL($baseIso) . '"
                AND cr1.provider = "' . pSQL($providerCode) . '"';

        $results = $db->executeS($sql);

        if (!$results) {
            return [];
        }

        $rates = [];
        foreach ($results as $row) {
            $rates[$row['quote_iso']] = (float) $row['rate'];
        }

        return $rates;
    }

    /**
     * Retrieves paginated historical rates within date range.
     *
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @param string $orderWay
     * @param int $days
     * @param string|null $providerCode
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistoricalRates(
        int $limit = 20,
        int $offset = 0,
        string $orderBy = 'date',
        string $orderWay = 'DESC',
        int $days = 30,
        ?string $providerCode = null
    ): array {
        $db = \Db::getInstance();

        $allowedOrderBy = ['date', 'base_iso', 'quote_iso', 'rate', 'provider', 'updated_at'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'date';
        }

        $orderWay = strtoupper($orderWay) === 'ASC' ? 'ASC' : 'DESC';

        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $activeCurrencies = $this->getActiveCurrencyIsoCodes();
        if (empty($activeCurrencies)) {
            return [];
        }

        $currencyList = implode('", "', array_map('pSQL', $activeCurrencies));

        $sql = 'SELECT id_currency_rate, date, base_iso, quote_iso, provider, rate, created_at, updated_at
                FROM ' . _DB_PREFIX_ . 'currency_rate
                WHERE date >= "' . pSQL($startDate) . '"
                AND date <= "' . pSQL($endDate) . '"
                AND base_iso IN ("' . $currencyList . '")
                AND quote_iso IN ("' . $currencyList . '")';

        if ($providerCode !== null) {
            $sql .= ' AND provider = "' . pSQL($providerCode) . '"';
        }

        $sql .= ' ORDER BY ' . pSQL($orderBy) . ' ' . $orderWay . '
                LIMIT ' . (int) $limit . '
                OFFSET ' . (int) $offset;

        $results = $db->executeS($sql);

        return $results ?: [];
    }

    /**
     * @param int $days
     * @param string|null $providerCode
     *
     * @return int
     */
    public function countHistoricalRates(int $days = 30, ?string $providerCode = null): int
    {
        $db = \Db::getInstance();

        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $activeCurrencies = $this->getActiveCurrencyIsoCodes();
        if (empty($activeCurrencies)) {
            return 0;
        }

        $currencyList = implode('", "', array_map('pSQL', $activeCurrencies));

        $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'currency_rate
                WHERE date >= "' . pSQL($startDate) . '"
                AND date <= "' . pSQL($endDate) . '"
                AND base_iso IN ("' . $currencyList . '")
                AND quote_iso IN ("' . $currencyList . '")';

        if ($providerCode !== null) {
            $sql .= ' AND provider = "' . pSQL($providerCode) . '"';
        }

        $count = $db->getValue($sql);

        return (int) $count;
    }

    /**
     * Gets ISO codes of active currencies.
     *
     * @return array<string>
     */
    private function getActiveCurrencyIsoCodes(): array
    {
        $currencies = \Currency::getCurrencies(false, true);
        $isoCodes = [];

        foreach ($currencies as $currency) {
            if (!empty($currency['iso_code'])) {
                $isoCodes[] = $currency['iso_code'];
            }
        }

        return $isoCodes;
    }
}
