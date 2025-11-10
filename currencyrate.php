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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Fetches and stores currency exchange rates from external providers.
 */
class CurrencyRate extends Module
{
    public function __construct()
    {
        $this->name = 'currencyrate';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Adam Krzemianowski';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Currency Rate', [], 'Modules.Currencyrate.Currencyrate');
        $this->description = $this->trans('Automatically collects and updates currency exchange rates.', [], 'Modules.Currencyrate.Currencyrate');

        $this->ps_versions_compliancy = ['min' => _PS_VERSION_, 'max' => _PS_VERSION_];
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Creates database tables and registers hooks.
     *
     * @return bool
     */
    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->createTables()) {
            return false;
        }

        if (!$this->registerHook('displayProductAdditionalInfo')) {
            return false;
        }

        Configuration::updateValue('CURRENCYRATE_PROVIDER_CODE', 'nbp');
        Configuration::updateValue('CURRENCYRATE_AUTO_UPDATE', 1);
        Configuration::updateValue('CURRENCYRATE_LAST_UPDATE', date('Y-m-d H:i:s'));
        Configuration::updateValue('CURRENCYRATE_QUOTE_ISO', 'EUR');

        return true;
    }

    /**
     * Removes configuration values and database tables.
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

        Configuration::deleteByName('CURRENCYRATE_PROVIDER_CODE');
        Configuration::deleteByName('CURRENCYRATE_AUTO_UPDATE');
        Configuration::deleteByName('CURRENCYRATE_LAST_UPDATE');
        Configuration::deleteByName('CURRENCYRATE_QUOTE_ISO');

        return $this->deleteTables();
    }

    /**
     * @return bool
     */
    protected function createTables(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'currency_rate` (
            `id_currency_rate` INT(11) NOT NULL AUTO_INCREMENT,
            `date` DATE NOT NULL,
            `base_iso` VARCHAR(3) NOT NULL,
            `quote_iso` VARCHAR(3) NOT NULL,
            `provider` VARCHAR(50) NOT NULL,
            `rate` DECIMAL(20,6) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_currency_rate`),
            UNIQUE KEY `unique_rate_entry` (`base_iso`, `quote_iso`, `date`, `provider`),
            KEY `idx_base_iso` (`base_iso`),
            KEY `idx_quote_iso` (`quote_iso`),
            KEY `idx_date` (`date`),
            KEY `idx_provider` (`provider`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @return bool
     */
    protected function deleteTables(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'currency_rate`';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Redirects to module configuration page.
     */
    public function getContent(): void
    {
        $route = $this->get('router')->generate('currency_rate_configuration');
        Tools::redirectAdmin($route);
    }

    /**
     * Displays product prices in alternative currencies.
     *
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        try {
            $displayService = $this->get('currencyrate.product_display');
            $currencyPrices = $displayService->getPricesForProduct($params['product']);

            if (empty($currencyPrices)) {
                return '';
            }

            $this->context->smarty->assign([
                'currency_prices' => $currencyPrices,
                'base_currency_iso' => $displayService->getBaseCurrencyIso(),
                'provider_code' => $displayService->getProviderCode(),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/product_currency_prices.tpl');
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog(
                sprintf('CurrencyRate hook error: %s', $e->getMessage()),
                3,
                null,
                'Module',
                (int) $this->id,
                true
            );

            return '';
        }
    }
}
