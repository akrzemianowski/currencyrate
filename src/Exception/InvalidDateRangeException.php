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
 * Invalid date range where start is after end.
 */
class InvalidDateRangeException extends CurrencyRateException
{
    /**
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     *
     * @return self
     */
    public static function fromAfterTo(\DateTimeImmutable $from, \DateTimeImmutable $to): self
    {
        return new self(
            sprintf(
                'Invalid date range: from (%s) must be before or equal to (%s)',
                $from->format('Y-m-d'),
                $to->format('Y-m-d')
            )
        );
    }
}
