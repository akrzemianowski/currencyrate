{*
* Currency Rate Module - Product Currency Prices Display
*
* Displays a table with product prices converted to different currencies
* using current exchange rates from the currency_rate table
*}

{if $currency_prices && count($currency_prices) > 0}
<div class="product-currency-prices card mt-3">
    <div class="card-header">
        <h3 class="h5 mb-0">{l s='Price in other currencies' d='Modules.Currencyrate.Product_currency_prices'}</h3>
        <p class="text-muted small mb-0">
            {l s='Prices calculated based on current exchange rates' d='Modules.Currencyrate.Product_currency_prices'}
        </p>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th scope="col">{l s='Currency' d='Modules.Currencyrate.Product_currency_prices'}</th>
                        <th scope="col" class="text-right">{l s='Price' d='Modules.Currencyrate.Product_currency_prices'}</th>
                        <th scope="col" class="text-right">{l s='Exchange Rate' d='Modules.Currencyrate.Product_currency_prices'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$currency_prices item=currency_price}
                        {if !$currency_price.is_base}
                        <tr>
                            <td>
                                <strong>{$currency_price.iso_code}</strong> - {$currency_price.name}
                            </td>
                            <td class="text-right">
                                <strong>{$currency_price.price_formatted}</strong>
                            </td>
                            <td class="text-right">
                                {$currency_price.rate|string_format:"%.4f"}
                            </td>
                        </tr>
                        {/if}
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted small">
        {l s='Exchange rates are updated automatically and may vary.' d='Modules.Currencyrate.Product_currency_prices'}
        {l s='Provider:' d='Modules.Currencyrate.Product_currency_prices'} <strong>{$provider_code|upper}</strong>
    </div>
</div>

<style>
.product-currency-prices {
    border: 1px solid #dee2e6;
}

.product-currency-prices .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem;
}

.product-currency-prices .card-header h3 {
    color: #212529;
    font-size: 1.125rem;
}

.product-currency-prices .table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.product-currency-prices .table td {
    vertical-align: middle;
}

.product-currency-prices .card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}
</style>
{/if}
