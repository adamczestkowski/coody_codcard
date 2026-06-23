<div class="row">
  <div class="col-md-12">
    <h3 class="h3 card-title">{l s='Płatność przelewem' mod='coody_codcard'}</h3>
    <p>
      {l s='Dziękujemy za zamówienie nr %reference% w sklepie %shop_name%.' sprintf=['%reference%' => $reference, '%shop_name%' => $shop_name] mod='coody_codcard'}
    </p>
    <p>
      {l s='Prosimy o przelew na kwotę %total% na poniższe konto. W tytule przelewu podaj numer zamówienia %reference%.' sprintf=['%total%' => $total, '%reference%' => $reference] mod='coody_codcard'}
    </p>
    {if isset($coody_codcard_account) && $coody_codcard_account|trim != ''}
      <dl class="definition-list">
        <dt>{l s='Numer konta' mod='coody_codcard'}{if isset($coody_codcard_currency_iso) && $coody_codcard_currency_iso} ({$coody_codcard_currency_iso|escape:'html':'UTF-8'}){/if}</dt>
        <dd>{$coody_codcard_account|escape:'html':'UTF-8'|nl2br nofilter}</dd>
      </dl>
    {/if}
    <p>
      <strong>{l s='Zamówienie zostanie zrealizowane po zaksięgowaniu wpłaty.' mod='coody_codcard'}</strong>
    </p>
    <p>
      {l s='W razie pytań skorzystaj z formularza' mod='coody_codcard'}
      <a href="{$contact_url|escape:'html':'UTF-8'}">{l s='kontaktowego' mod='coody_codcard'}</a>.
    </p>
  </div>
</div>
