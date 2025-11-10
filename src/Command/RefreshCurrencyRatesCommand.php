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

namespace CurrencyRate\Command;

use CurrencyRate\Service\SyncCurrencyRateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'prestashop:currency-rate:refresh',
    description: 'Refresh currency exchange rates from external provider'
)]
class RefreshCurrencyRatesCommand extends Command
{
    public function __construct(
        private SyncCurrencyRateService $syncService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'base',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Base currency ISO code (default: shop default currency)',
                null
            )
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Number of days to fetch historical rates',
                '30'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command refreshes currency exchange rates from the configured provider.

This command automatically synchronizes rates for all active currencies in your shop (excluding the base currency).

Usage examples:
  <info>php bin/console %command.name%</info>
  Fetch rates for the last 30 days for all active currencies

  <info>php bin/console %command.name% --days=7</info>
  Fetch rates for the last 7 days for all active currencies

  <info>php bin/console %command.name% --base=PLN --days=90</info>
  Fetch PLN to all active currencies rates for the last 90 days
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!\Configuration::get('CURRENCYRATE_AUTO_UPDATE')) {
            $io->warning('Currency rate auto-update is disabled. Enable it in module configuration to use this command.');
            return Command::FAILURE;
        }

        $baseIso = $input->getOption('base');
        if ($baseIso === null) {
            $baseId = (int) \Configuration::get('PS_CURRENCY_DEFAULT');
            $baseIso = \Currency::getIsoCodeById($baseId);

            if (!$baseIso) {
                $io->error('Could not determine base currency. Please specify --base option.');
                return Command::FAILURE;
            }
        }

        $activeCurrencies = \Currency::getCurrencies(false, true);
        if (empty($activeCurrencies)) {
            $io->error('No active currencies found in the shop.');
            return Command::FAILURE;
        }

        $quoteCurrencies = array_filter(
            array_map(fn($currency) => $currency['iso_code'], $activeCurrencies),
            fn($isoCode) => $isoCode !== $baseIso
        );

        if (empty($quoteCurrencies)) {
            $io->warning(sprintf(
                'No quote currencies to synchronize. Only base currency (%s) is active.',
                $baseIso
            ));
            return Command::SUCCESS;
        }

        $days = (int) $input->getOption('days');
        if ($days <= 0) {
            $io->error('Days must be a positive integer.');
            return Command::FAILURE;
        }

        $to = new \DateTimeImmutable('today');
        $from = $to->sub(new \DateInterval(sprintf('P%dD', $days)));

        $io->section('Currency Rate Synchronization');
        $io->text([
            sprintf('Base Currency: <info>%s</info>', $baseIso),
            sprintf('Quote Currencies: <info>%s</info>', implode(', ', $quoteCurrencies)),
            sprintf('Date Range: <info>%s</info> to <info>%s</info>',
                $from->format('Y-m-d'),
                $to->format('Y-m-d')
            ),
        ]);

        $io->newLine();

        $totalCount = 0;
        $failedCurrencies = [];

        foreach ($quoteCurrencies as $quoteIso) {
            $io->writeln(sprintf('Fetching rates for <info>%s/%s</info>...', $baseIso, $quoteIso));

            try {
                $count = $this->syncService->sync($baseIso, $quoteIso, $from, $to);
                $totalCount += $count;

                $io->writeln(sprintf(
                    '  <comment>✓</comment> Synchronized %d rate%s for %s',
                    $count,
                    $count !== 1 ? 's' : '',
                    $quoteIso
                ));
            } catch (\Exception $e) {
                $failedCurrencies[$quoteIso] = $e->getMessage();
                $io->writeln(sprintf(
                    '  <error>✗</error> Failed to synchronize %s: %s',
                    $quoteIso,
                    $e->getMessage()
                ));

                if ($output->isVerbose()) {
                    $io->text($e->getTraceAsString());
                }
            }
        }

        $io->newLine();

        if (empty($failedCurrencies)) {
            $io->success(sprintf(
                'Successfully synchronized %d currency rate%s for %d currenc%s.',
                $totalCount,
                $totalCount !== 1 ? 's' : '',
                count($quoteCurrencies),
                count($quoteCurrencies) !== 1 ? 'ies' : 'y'
            ));

            \Configuration::updateValue('CURRENCYRATE_LAST_UPDATE', date('Y-m-d H:i:s'));

            return Command::SUCCESS;
        } else {
            $successCount = count($quoteCurrencies) - count($failedCurrencies);
            $io->warning(sprintf(
                'Partially synchronized: %d successful, %d failed.',
                $successCount,
                count($failedCurrencies)
            ));

            $io->text('Failed currencies:');
            foreach ($failedCurrencies as $currency => $error) {
                $io->text(sprintf('  - <error>%s</error>: %s', $currency, $error));
            }

            if ($totalCount > 0) {
                \Configuration::updateValue('CURRENCYRATE_LAST_UPDATE', date('Y-m-d H:i:s'));
            }

            return Command::FAILURE;
        }
    }
}
