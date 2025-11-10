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

namespace CurrencyRate\Factory;

use Configuration;
use CurrencyRate\Registry\CurrencyRateProviderRegistry;
use CurrencyRate\Contracts\CurrencyRateProviderInterface;

/**
 * Creates provider instances based on configuration.
 */
final class CurrencyRateProviderFactory
{
    public const CONFIG_KEY = 'CURRENCYRATE_PROVIDER_CODE';

    /**
     * @param CurrencyRateProviderRegistry $registry
     */
    public function __construct(private CurrencyRateProviderRegistry $registry) {}

    /**
     * @return CurrencyRateProviderInterface
     */
    public function forCurrentConfig(): CurrencyRateProviderInterface
    {
        $code = (string) \Configuration::get(static::CONFIG_KEY) ?: 'nbp';

        return $this->registry->get($code);
    }
}
