<section id="coody_codcard-paymentOptions-wire-additionalInformation">
  {if isset($coody_codcard_wire_intro_content) && $coody_codcard_wire_intro_content|trim != ''}
    <p class="mt-2 mb-2">{$coody_codcard_wire_intro_content|escape:'html':'UTF-8'}</p>
  {/if}
  {if isset($coody_codcard_account) && $coody_codcard_account|trim != ''}
    <dl class="definition-list">
      <dt>{l s='Numer konta' mod='coody_codcard'}{if isset($coody_codcard_currency_iso) && $coody_codcard_currency_iso} ({$coody_codcard_currency_iso|escape:'html':'UTF-8'}){/if}</dt>
      <dd>{$coody_codcard_account|escape:'html':'UTF-8'|nl2br nofilter}</dd>
      {if isset($coody_codcard_total) && $coody_codcard_total}
        <dt>{l s='Kwota do przelewu' mod='coody_codcard'}</dt>
        <dd>{$coody_codcard_total|escape:'html':'UTF-8'}</dd>
      {/if}
    </dl>
  {/if}
</section>
