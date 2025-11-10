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

namespace CurrencyRate\Contracts;

use CurrencyRate\DTO\DateRange;
use CurrencyRate\DTO\CurrencyRatePoint;

/**
 * Contract for external currency rate providers.
 */
interface CurrencyRateProviderInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @return string
     */
    public function getBaseCurrency(): string;

    /**
     * Retrieves exchange rates for a date range.
     *
     * @param string $baseIso
     * @param string $quoteIso
     * @param DateRange $range
     *
     * @return iterable<CurrencyRatePoint>
     */
    public function fetchHistory(string $baseIso, string $quoteIso, DateRange $range): iterable;
}
