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

namespace CurrencyRate\Exception;

/**
 * Errors from external rate providers.
 */
class ProviderException extends CurrencyRateException
{
    /**
     * @param string $provider
     * @param string $field
     *
     * @return self
     */
    public static function missingField(string $provider, string $field): self
    {
        return new self(sprintf('%s API: missing "%s" field', $provider, $field));
    }

    /**
     * @param float $rate
     *
     * @return self
     */
    public static function invalidRate(float $rate): self
    {
        return new self(sprintf('Invalid rate: %s (must be positive)', $rate));
    }

    /**
     * @param string $code
     *
     * @return self
     */
    public static function unknownProvider(string $code): self
    {
        return new self(sprintf('Unknown provider: %s', $code));
    }
}
