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

namespace CurrencyRate\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use CurrencyRate\Contracts\CurrencyRateProviderInterface;
use CurrencyRate\DTO\DateRange;
use CurrencyRate\DTO\CurrencyRatePoint;
use CurrencyRate\Exception\ProviderException;

/**
 * Fetches rates from Polish National Bank API.
 */
final class NbpCurrencyRateProvider implements CurrencyRateProviderInterface
{
    private const BASE_CURRENCY = 'PLN';
    private const CODE = 'nbp';

    /**
     * @param HttpClientInterface $http
     */
    public function __construct(private HttpClientInterface $http) {}

    /**
     * @return string
     */
    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * @return string
     */
    public function getBaseCurrency(): string
    {
        return self::BASE_CURRENCY;
    }

    /**
     * @param string $baseIso
     * @param string $quoteIso
     * @param DateRange $range
     *
     * @return iterable<CurrencyRatePoint>
     */
    public function fetchHistory(string $baseIso, string $quoteIso, DateRange $range): iterable
    {
        $url = sprintf(
            'https://api.nbp.pl/api/exchangerates/rates/A/%s/%s/%s/?format=json',
            strtolower($quoteIso),
            $range->from->format('Y-m-d'),
            $range->to->format('Y-m-d')
        );

        $resp = $this->http->request('GET', $url, ['timeout' => 15]);
        $json = $resp->toArray();

        if (!isset($json['rates']) || !is_array($json['rates'])) {
            throw ProviderException::missingField('NBP', 'rates');
        }

        foreach ($json['rates'] as $row) {
            if (!isset($row['effectiveDate'])) {
                throw ProviderException::missingField('NBP', 'effectiveDate');
            }
            if (!isset($row['mid'])) {
                throw ProviderException::missingField('NBP', 'mid');
            }

            $rate = (float) $row['mid'];
            if ($rate <= 0 || !is_finite($rate)) {
                throw ProviderException::invalidRate($rate);
            }

            yield new CurrencyRatePoint(
                new \DateTimeImmutable($row['effectiveDate']),
                $baseIso, $quoteIso,
                $this->getCode(),
                $rate
            );
        }
    }
}
