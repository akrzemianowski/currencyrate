{extends file='page.tpl'}

{block name='page_title'}
  {l s='Historical Currency Rates (Last 30 Days)' d='Modules.Currencyrate.History'}
{/block}

{block name='page_content'}
  <div class="currency-rate-history">
    <div class="alert alert-info mb-3">
      <strong>{l s='Provider' d='Modules.Currencyrate.History'}:</strong> <span class="badge badge-primary">{$providerCode|upper}</span>
      <br>
      <strong>{l s='Total records' d='Modules.Currencyrate.History'}:</strong> {$totalRecords}
    </div>

    {if $rates && count($rates) > 0}
      <div class="table-responsive">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>
                <a href="{$historyUrl}?page={$currentPage}&orderBy=date&orderWay={if $orderBy == 'date' && $orderWay == 'ASC'}DESC{else}ASC{/if}" class="text-decoration-none">
                  {l s='Date' d='Modules.Currencyrate.History'}
                  {if $orderBy == 'date'}
                    <i class="material-icons">{if $orderWay == 'ASC'}arrow_upward{else}arrow_downward{/if}</i>
                  {/if}
                </a>
              </th>
              <th>
                <a href="{$historyUrl}?page={$currentPage}&orderBy=base_iso&orderWay={if $orderBy == 'base_iso' && $orderWay == 'ASC'}DESC{else}ASC{/if}" class="text-decoration-none">
                  {l s='Base Currency' d='Modules.Currencyrate.History'}
                  {if $orderBy == 'base_iso'}
                    <i class="material-icons">{if $orderWay == 'ASC'}arrow_upward{else}arrow_downward{/if}</i>
                  {/if}
                </a>
              </th>
              <th>
                <a href="{$historyUrl}?page={$currentPage}&orderBy=quote_iso&orderWay={if $orderBy == 'quote_iso' && $orderWay == 'ASC'}DESC{else}ASC{/if}" class="text-decoration-none">
                  {l s='Quote Currency' d='Modules.Currencyrate.History'}
                  {if $orderBy == 'quote_iso'}
                    <i class="material-icons">{if $orderWay == 'ASC'}arrow_upward{else}arrow_downward{/if}</i>
                  {/if}
                </a>
              </th>
              <th>
                <a href="{$historyUrl}?page={$currentPage}&orderBy=rate&orderWay={if $orderBy == 'rate' && $orderWay == 'ASC'}DESC{else}ASC{/if}" class="text-decoration-none">
                  {l s='Exchange Rate' d='Modules.Currencyrate.History'}
                  {if $orderBy == 'rate'}
                    <i class="material-icons">{if $orderWay == 'ASC'}arrow_upward{else}arrow_downward{/if}</i>
                  {/if}
                </a>
              </th>
              <th>
                <a href="{$historyUrl}?page={$currentPage}&orderBy=updated_at&orderWay={if $orderBy == 'updated_at' && $orderWay == 'ASC'}DESC{else}ASC{/if}" class="text-decoration-none">
                  {l s='Last Updated' d='Modules.Currencyrate.History'}
                  {if $orderBy == 'updated_at'}
                    <i class="material-icons">{if $orderWay == 'ASC'}arrow_upward{else}arrow_downward{/if}</i>
                  {/if}
                </a>
              </th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$rates item=rate}
              <tr>
                <td>{$rate.date}</td>
                <td><span class="badge badge-primary">{$rate.base_iso}</span></td>
                <td><span class="badge badge-info">{$rate.quote_iso}</span></td>
                <td><strong>{$rate.rate|string_format:"%.6f"}</strong></td>
                <td>{$rate.updated_at}</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>

      {* Pagination *}
      {if $totalPages > 1}
        <nav aria-label="Page navigation" class="mt-4">
          <ul class="pagination justify-content-center">
            {* First page *}
            {if $currentPage > 1}
              <li class="page-item">
                <a class="page-link" href="{$historyUrl}?page=1&orderBy={$orderBy}&orderWay={$orderWay}">
                  <i class="material-icons">first_page</i>
                </a>
              </li>
              <li class="page-item">
                <a class="page-link" href="{$historyUrl}?page={$currentPage - 1}&orderBy={$orderBy}&orderWay={$orderWay}">
                  <i class="material-icons">chevron_left</i>
                </a>
              </li>
            {else}
              <li class="page-item disabled">
                <span class="page-link"><i class="material-icons">first_page</i></span>
              </li>
              <li class="page-item disabled">
                <span class="page-link"><i class="material-icons">chevron_left</i></span>
              </li>
            {/if}

            {* Page numbers *}
            {for $page=$startPage to $endPage}
              <li class="page-item {if $page == $currentPage}active{/if}">
                <a class="page-link" href="{$historyUrl}?page={$page}&orderBy={$orderBy}&orderWay={$orderWay}">
                  {$page}
                </a>
              </li>
            {/for}

            {* Last page *}
            {if $currentPage < $totalPages}
              <li class="page-item">
                <a class="page-link" href="{$historyUrl}?page={$currentPage + 1}&orderBy={$orderBy}&orderWay={$orderWay}">
                  <i class="material-icons">chevron_right</i>
                </a>
              </li>
              <li class="page-item">
                <a class="page-link" href="{$historyUrl}?page={$totalPages}&orderBy={$orderBy}&orderWay={$orderWay}">
                  <i class="material-icons">last_page</i>
                </a>
              </li>
            {else}
              <li class="page-item disabled">
                <span class="page-link"><i class="material-icons">chevron_right</i></span>
              </li>
              <li class="page-item disabled">
                <span class="page-link"><i class="material-icons">last_page</i></span>
              </li>
            {/if}
          </ul>
        </nav>

        <div class="text-center text-muted">
          {l s='Showing page' d='Modules.Currencyrate.History'} {$currentPage} {l s='of' d='Modules.Currencyrate.History'} {$totalPages}
        </div>
      {/if}
    {else}
      <div class="alert alert-warning">
        {l s='No historical data available. Please check back later.' d='Modules.Currencyrate.History'}
      </div>
    {/if}
  </div>
{/block}
