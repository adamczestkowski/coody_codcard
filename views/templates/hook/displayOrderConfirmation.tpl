<div class="row">
  <div class="col-md-12">
    <h3 class="h3 card-title">{l s='Płatność kartą przy odbiorze' mod='coody_codcard'}</h3>
    <p>
      {l s='Dziękujemy za zamówienie nr %reference% w sklepie %shop_name%.' sprintf=['%reference%' => $reference, '%shop_name%' => $shop_name] mod='coody_codcard'}
    </p>
    <p>
      {l s='Kwota do zapłaty %total% zostanie pobrana kartą u kuriera lub w punkcie odbioru przesyłki.' sprintf=['%total%' => $total] mod='coody_codcard'}
    </p>
    <p>
      {l s='W razie pytań skorzystaj z formularza %start_link%kontaktowego%end_link%.' sprintf=['%start_link%' => "<a href=\"{$contact_url}\">", '%end_link%' => '</a>'] mod='coody_codcard'}
    </p>
  </div>
</div>
