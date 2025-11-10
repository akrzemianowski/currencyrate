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

namespace CurrencyRate\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class CurrencyRateDataConfiguration implements DataConfigurationInterface
{
    public const CURRENCYRATE_PROVIDER_CODE = 'CURRENCYRATE_PROVIDER_CODE';
    public const CURRENCYRATE_AUTO_UPDATE = 'CURRENCYRATE_AUTO_UPDATE';

    private ConfigurationInterface $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $return = [];

        $return['provider_code'] = $this->configuration->get(static::CURRENCYRATE_PROVIDER_CODE);
        $return['auto_update'] = (int) $this->configuration->get(static::CURRENCYRATE_AUTO_UPDATE);

        return $return;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $this->configuration->set(static::CURRENCYRATE_PROVIDER_CODE, $configuration['provider_code']);
            $this->configuration->set(static::CURRENCYRATE_AUTO_UPDATE, (int) $configuration['auto_update']);

            $this->configuration->set('CURRENCYRATE_LAST_UPDATE', date('Y-m-d H:i:s'));
        } else {
            $errors[] = 'Invalid configuration data';
        }

        return $errors;
    }

    public function validateConfiguration(array $configuration): bool
    {
        return isset($configuration['provider_code']) && isset($configuration['auto_update']);
    }
}
