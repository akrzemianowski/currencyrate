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

namespace CurrencyRate\Controller;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CurrencyRate\Service\SyncCurrencyRateService;
use CurrencyRate\Exception\ProviderException;
use Psr\Log\LoggerInterface;

class CurrencyRateConfigurationController extends FrameworkBundleAdminController
{
    private SyncCurrencyRateService $syncService;
    private LoggerInterface $logger;

    public function setSyncCurrencyRateService(SyncCurrencyRateService $syncService): void
    {
        $this->syncService = $syncService;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function getSyncService(): SyncCurrencyRateService
    {
        return $this->syncService ?? throw new \RuntimeException('SyncService not injected');
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? throw new \RuntimeException('Logger not injected');
    }

    public function index(Request $request): Response
    {
        $configFormDataHandler = $this->get('currencyrate.form.currency_rate_configuration_form_data_handler');

        $configForm = $configFormDataHandler->getForm();
        $configForm->handleRequest($request);

        if ($configForm->isSubmitted() && $configForm->isValid()) {
            $errors = $configFormDataHandler->save($configForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('currency_rate_configuration');
            }

            $this->flashErrors($errors);
        }

        $ratesCount = \Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'currency_rate`');
        $lastUpdate = \Configuration::get('CURRENCYRATE_LAST_UPDATE');

        $currencies = \Currency::getCurrencies(true);
        $currencyCodes = array_map(function($currency) {
            return is_object($currency) ? $currency->iso_code : $currency['iso_code'];
        }, $currencies);

        $context = \Context::getContext();
        $historyUrl = $context->link->getModuleLink('currencyrate', 'history');

        return $this->render('@Modules/currencyrate/views/templates/admin/configure.html.twig', [
            'configurationForm' => $configForm->createView(),
            'ratesCount' => $ratesCount,
            'lastUpdate' => $lastUpdate ?: $this->trans('Never', 'Modules.Currencyrate.Admin'),
            'currencyCodes' => $currencyCodes,
            'currencyCount' => count($currencyCodes),
            'historyUrl' => $historyUrl,
        ]);
    }

    public function fetchHistoricalRates(Request $request): Response
    {
        try {
            $endDate = new \DateTimeImmutable();
            $startDate = new \DateTimeImmutable('-30 days');

            $currencies = \Currency::getCurrencies(true);
            $currencyCodes = array_map(function($currency) {
                return is_object($currency) ? $currency->iso_code : $currency['iso_code'];
            }, $currencies);

            if (empty($currencyCodes)) {
                $this->addFlash('warning', $this->trans('No active currencies found in the store.', 'Modules.Currencyrate.Admin'));
                return $this->redirectToRoute('currency_rate_configuration');
            }

            $baseCurrency = \Currency::getIsoCodeById((int)\Configuration::get('PS_CURRENCY_DEFAULT')) ?: 'PLN';

            $totalSynced = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($currencyCodes as $quoteCurrency) {
                if ($quoteCurrency === $baseCurrency) {
                    continue;
                }

                try {
                    $count = $this->getSyncService()->sync($baseCurrency, $quoteCurrency, $startDate, $endDate);
                    $totalSynced += $count;
                } catch (ProviderException $e) {
                    $errorCount++;
                    $errors[] = $quoteCurrency;
                    $this->getLogger()->error(
                        sprintf('[currencyrate] Sync failed: %s/%s', $baseCurrency, $quoteCurrency),
                        ['error' => $e->getMessage()]
                    );
                }
            }

            \Configuration::updateValue('CURRENCYRATE_LAST_UPDATE', date('Y-m-d H:i:s'));

            if ($errorCount === 0) {
                $message = sprintf(
                    $this->trans('Successfully synced %d currency rates for period %s to %s', 'Modules.Currencyrate.Admin'),
                    $totalSynced,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                );
                $this->addFlash('success', $message);
            } else {
                $message = sprintf(
                    $this->trans('Synced %d rates with %d errors for currencies: %s. Check logs for details.', 'Modules.Currencyrate.Admin'),
                    $totalSynced,
                    $errorCount,
                    implode(', ', $errors)
                );
                $this->addFlash('warning', $message);
            }
        } catch (\Throwable $e) {
            $this->getLogger()->error('[currencyrate] Sync error', ['error' => $e->getMessage()]);
            $this->addFlash('error', $this->trans('Error syncing rates: ', 'Modules.Currencyrate.Admin') . $e->getMessage());
        }

        return $this->redirectToRoute('currency_rate_configuration');
    }
}
