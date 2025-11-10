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
 * Fetches rates from Frankfurter API (European Central Bank data).
 */
final class FrankfurterCurrencyRateProvider implements CurrencyRateProviderInterface
{
    private const BASE_CURRENCY = 'EUR';
    private const CODE = 'frankfurter';
    private const API_URL = 'https://api.frankfurter.dev/v1';

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
            '%s/%s..%s?base=%s&symbols=%s',
            self::API_URL,
            $range->from->format('Y-m-d'),
            $range->to->format('Y-m-d'),
            strtoupper($baseIso),
            strtoupper($quoteIso)
        );

        $resp = $this->http->request('GET', $url, ['timeout' => 15]);
        $json = $resp->toArray();

        if (!isset($json['rates']) || !is_array($json['rates'])) {
            throw ProviderException::missingField('Frankfurter', 'rates');
        }

        foreach ($json['rates'] as $date => $currencies) {
            if (!is_array($currencies)) {
                continue;
            }

            if (isset($currencies[$quoteIso])) {
                $apiRate = (float) $currencies[$quoteIso];

                if ($apiRate <= 0 || !is_finite($apiRate)) {
                    throw ProviderException::invalidRate($apiRate);
                }

                $invertedRate = 1.0 / $apiRate;

                yield new CurrencyRatePoint(
                    new \DateTimeImmutable($date),
                    $baseIso,
                    $quoteIso,
                    $this->getCode(),
                    $invertedRate
                );
            }
        }
    }
}
