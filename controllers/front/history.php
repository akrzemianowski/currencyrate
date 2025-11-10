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

/**
 * Front controller for currency rate history view.
 */
class CurrencyRateHistoryModuleFrontController extends ModuleFrontController
{
    /**
     * Loads and displays historical currency rates.
     */
    public function initContent(): void
    {
        parent::initContent();

        $page = max(1, (int) Tools::getValue('page', 1));
        $limit = 20;
        $orderBy = Tools::getValue('orderBy', 'date');
        $orderWay = Tools::getValue('orderWay', 'DESC');
        $providerCode = Configuration::get('CURRENCYRATE_PROVIDER_CODE') ?: 'nbp';
        $offset = ($page - 1) * $limit;
        $repository = $this->get('CurrencyRate\Repository\CurrencyRateRepository');
        $rates = $repository->getHistoricalRates($limit, $offset, $orderBy, $orderWay, 30, $providerCode);
        $totalRecords = $repository->countHistoricalRates(30, $providerCode);
        $totalPages = (int) ceil($totalRecords / $limit);
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);

        $this->context->smarty->assign([
            'rates' => $rates,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'orderBy' => $orderBy,
            'orderWay' => $orderWay,
            'limit' => $limit,
            'startPage' => $startPage,
            'endPage' => $endPage,
            'providerCode' => $providerCode,
            'historyUrl' => $this->context->link->getModuleLink('currencyrate', 'history'),
        ]);

        $this->setTemplate('module:currencyrate/views/templates/front/history.tpl');
    }

    /**
     * @return array<string, mixed>
     */
    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->module->getTranslator()->trans('Historical Currency Rates (Last 30 Days)', [], 'Modules.Currencyrate.Shop'),
            'url' => $this->context->link->getModuleLink('currencyrate', 'history'),
        ];

        return $breadcrumb;
    }

    /**
     * Loads page assets.
     */
    public function setMedia(): void
    {
        parent::setMedia();

        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/front.css');
    }
}
