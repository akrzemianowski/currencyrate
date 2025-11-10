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

use CurrencyRate\Factory\CurrencyRateProviderFactory;
use CurrencyRate\DTO\DateRange;
use CurrencyRate\Repository\CurrencyRateRepository;

final class SyncCurrencyRateService
{
    public function __construct(
        private CurrencyRateProviderFactory $factory,
        private CurrencyRateRepository $repository,
        private CurrencyPriceCalculatorService $priceCalculator
    ) {}

    public function sync(string $baseIso, string $quoteIso, \DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $provider = $this->factory->forCurrentConfig();
        $range = new DateRange($from, $to);

        $count = 0;
        foreach ($provider->fetchHistory($baseIso, $quoteIso, $range) as $point) {
            $this->repository->upsert($point);
            $count++;
        }

        $this->priceCalculator->clearRatesCache();

        return $count;
    }
}
