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

namespace CurrencyRate\ValueObject;

use CurrencyRate\Exception\InvalidIsoCodeException;

/**
 * Validated ISO 4217 currency code.
 */
final class CurrencyIsoCode
{
    private string $code;

    /**
     * @param string $code
     */
    public function __construct(string $code)
    {
        $normalized = strtoupper(trim($code));

        if (!preg_match('/^[A-Z]{3}$/', $normalized)) {
            throw InvalidIsoCodeException::forCode($code);
        }

        $this->code = $normalized;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->code;
    }

    /**
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
