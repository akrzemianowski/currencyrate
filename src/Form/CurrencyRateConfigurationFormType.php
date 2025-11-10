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

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class CurrencyRateConfigurationFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('provider_code', ChoiceType::class, [
                'label' => $this->trans('API Service', 'Modules.Currencyrate.Admin'),
                'choices' => [
                    $this->trans('NBP (Narodowy Bank Polski)', 'Modules.Currencyrate.Admin') => 'nbp',
                    $this->trans('Frankfurter (European Central Bank)', 'Modules.Currencyrate.Admin') => 'frankfurter',
                ],
                'required' => true,
                'help' => $this->trans('Select the API service to fetch currency rates from.', 'Modules.Currencyrate.Admin'),
            ])
            ->add('auto_update', ChoiceType::class, [
                'label' => $this->trans('Auto Update', 'Modules.Currencyrate.Admin'),
                'choices' => [
                    $this->trans('Enabled', 'Modules.Currencyrate.Admin') => 1,
                    $this->trans('Disabled', 'Modules.Currencyrate.Admin') => 0,
                ],
                'expanded' => true,
                'required' => true,
                'help' => $this->trans('Automatically update currency rates daily via cron.', 'Modules.Currencyrate.Admin'),
            ]);
    }
}
