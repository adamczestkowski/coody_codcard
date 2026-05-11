<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Coody_Codcard extends PaymentModule
{
    const HOOKS = [
        'displayOrderConfirmation',
        'paymentOptions',
    ];

    const CONFIG_OS_CODCARD_VALIDATION = 'COODY_OS_CODCARD_VALIDATION';
    const CONFIG_EXTRA_CONTENT = 'COODY_CODCARD_EXTRA_CONTENT';

    /**
     * Domyślna treść extra content per ISO języka.
     */
    public static $defaultExtraContent = [
        'pl' => 'Zapłacisz kartą u kuriera lub w punkcie odbioru — przygotuj kartę płatniczą w momencie odbioru przesyłki.',
        'en' => 'You will pay by card on delivery — please have your payment card ready when receiving the parcel.',
        'de' => 'Sie zahlen per Karte bei Lieferung — halten Sie Ihre Zahlungskarte bei der Zustellung bereit.',
        'fr' => 'Vous paierez par carte à la livraison — préparez votre carte de paiement au moment de la réception du colis.',
        'es' => 'Pagará con tarjeta a la entrega — tenga lista su tarjeta cuando reciba el paquete.',
        'it' => 'Pagherai con carta alla consegna — tieni pronta la tua carta al momento della ricezione del pacco.',
        'cs' => 'Zaplatíte kartou při doručení — připravte si platební kartu při převzetí zásilky.',
        'sk' => 'Zaplatíte kartou pri doručení — pripravte si platobnú kartu pri prevzatí zásielky.',
        'uk' => 'Ви оплатите карткою при отриманні — підготуйте платіжну картку під час отримання посилки.',
        'ru' => 'Вы оплатите картой при получении — приготовьте платежную карту в момент получения посылки.',
    ];

    public function __construct()
    {
        $this->name = 'coody_codcard';
        $this->tab = 'payments_gateways';
        $this->author = 'Coody';
        $this->version = '1.0.0';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->controllers = ['validation'];
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Płatność kartą przy odbiorze');
        $this->description = $this->l('Pozwala klientom zapłacić kartą u kuriera lub w punkcie odbioru.');
        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować moduł?');
    }

    public function install()
    {
        return parent::install()
            && (bool) $this->registerHook(static::HOOKS)
            && $this->installOrderState()
            && $this->installDefaultExtraContent();
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CONFIG_EXTRA_CONTENT);

        return parent::uninstall();
    }

    /**
     * Zapisuje domyślną treść extra content dla każdego zainstalowanego języka.
     */
    public function installDefaultExtraContent()
    {
        $values = [];
        foreach (Language::getLanguages(false) as $lang) {
            $iso = Tools::strtolower($lang['iso_code']);
            $values[(int) $lang['id_lang']] = self::$defaultExtraContent[$iso] ?? self::$defaultExtraContent['en'];
        }

        return Configuration::updateValue(self::CONFIG_EXTRA_CONTENT, $values, true);
    }

    /**
     * Renderuje formularz konfiguracyjny w panelu modułu.
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $values = [];
            foreach (Language::getLanguages(false) as $lang) {
                $values[(int) $lang['id_lang']] = Tools::getValue(self::CONFIG_EXTRA_CONTENT . '_' . (int) $lang['id_lang'], '');
            }
            Configuration::updateValue(self::CONFIG_EXTRA_CONTENT, $values, true);
            $output .= $this->displayConfirmation($this->l('Ustawienia zapisane.'));
        }

        return $output . $this->renderConfigForm();
    }

    /**
     * Zwraca extra content dla danego języka (lub fallback EN / pierwsza wartość).
     */
    public function getExtraContent($idLang)
    {
        $value = Configuration::get(self::CONFIG_EXTRA_CONTENT, (int) $idLang);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $fallback = Configuration::get(self::CONFIG_EXTRA_CONTENT, $defaultLang);
        if (is_string($fallback) && $fallback !== '') {
            return $fallback;
        }

        return self::$defaultExtraContent['en'];
    }

    private function renderConfigForm()
    {
        $languages = Language::getLanguages(false);
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Treść wyświetlana przy opcji płatności'),
                    'icon' => 'icon-credit-card',
                ],
                'input' => [
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Extra content'),
                        'name' => self::CONFIG_EXTRA_CONTENT,
                        'lang' => true,
                        'cols' => 60,
                        'rows' => 5,
                        'autoload_rte' => true,
                        'desc' => $this->l('Treść pokazywana klientowi obok opcji "Płatność kartą przy odbiorze" na etapie wyboru płatności. Możesz używać HTML.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $defaultLangId;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->toolbar_scroll = false;
        $helper->submit_action = 'submit' . $this->name;
        $helper->languages = $languages;

        $fieldsValue = [];
        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $stored = Configuration::get(self::CONFIG_EXTRA_CONTENT, $idLang);
            if (!is_string($stored) || $stored === '') {
                $iso = Tools::strtolower($lang['iso_code']);
                $stored = self::$defaultExtraContent[$iso] ?? self::$defaultExtraContent['en'];
            }
            $fieldsValue[self::CONFIG_EXTRA_CONTENT][$idLang] = $stored;
        }

        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $languages,
            'id_language' => $defaultLangId,
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * @param array{cookie: Cookie, cart: Cart, altern: int} $params
     *
     * @return array|PaymentOption[]
     */
    public function hookPaymentOptions(array $params)
    {
        if (empty($params['cart'])) {
            return [];
        }

        /** @var Cart $cart */
        $cart = $params['cart'];

        if ($cart->isVirtualCart()) {
            return [];
        }

        if (!$this->checkCurrency($cart)) {
            return [];
        }

        $this->context->smarty->assign([
            'coody_codcard_extra_content' => $this->getExtraContent((int) $this->context->language->id),
        ]);

        $option = new PaymentOption();
        $option->setModuleName($this->name);
        $option->setCallToActionText($this->l('Płatność kartą przy odbiorze'));
        $option->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true));
        $option->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/card-on-delivery.svg'));
        $option->setAdditionalInformation($this->fetch('module:coody_codcard/views/templates/hook/paymentOptions-additionalInformation.tpl'));

        return [$option];
    }

    /**
     * @param array{cookie: Cookie, cart: Cart, altern: int, order: Order, objOrder: Order} $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        /** @var Order $order */
        $order = isset($params['objOrder']) ? $params['objOrder'] : $params['order'];

        if (!Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'shop_name' => $this->context->shop->name,
            'total' => $this->context->getCurrentLocale()->formatPrice(
                $order->getOrdersTotalPaid(),
                (new Currency($order->id_currency))->iso_code
            ),
            'reference' => $order->reference,
            'contact_url' => $this->context->link->getPageLink('contact', true),
        ]);

        return $this->fetch('module:coody_codcard/views/templates/hook/displayOrderConfirmation.tpl');
    }

    /**
     * Sprawdza, czy aktualna waluta koszyka jest dostępna dla modułu.
     */
    public function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency((int) $cart->id_currency);
        $currencies_module = $this->getCurrency((int) $cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ((int) $currency_order->id === (int) $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Instaluje (lub odzyskuje) niestandardowy stan zamówienia.
     */
    public function installOrderState()
    {
        $existingId = (int) Configuration::getGlobalValue(self::CONFIG_OS_CODCARD_VALIDATION);
        if ($existingId) {
            $existing = new OrderState($existingId);
            if (Validate::isLoadedObject($existing) && $existing->module_name === $this->name) {
                return true;
            }
        }

        $names = [
            'pl' => 'Oczekiwanie na płatność kartą przy odbiorze',
            'en' => 'Awaiting card-on-delivery payment',
            'de' => 'Warten auf Kartenzahlung bei Lieferung',
            'fr' => 'En attente de paiement par carte à la livraison',
            'es' => 'Esperando pago con tarjeta contra entrega',
            'it' => 'In attesa pagamento con carta alla consegna',
            'cs' => 'Čeká se na platbu kartou při doručení',
            'sk' => 'Čaká sa na platbu kartou pri doručení',
            'uk' => 'Очікується платіж карткою при доставці',
            'ru' => 'Ожидается оплата картой при доставке',
        ];

        $nameByLangId = [];
        foreach (Language::getLanguages(false) as $lang) {
            $iso = Tools::strtolower($lang['iso_code']);
            $nameByLangId[(int) $lang['id_lang']] = $names[$iso] ?? $names['en'];
        }

        $orderState = new OrderState();
        $orderState->module_name = $this->name;
        $orderState->name = $nameByLangId;
        $orderState->color = '#34219E';
        $orderState->logable = false;
        $orderState->paid = false;
        $orderState->invoice = false;
        $orderState->shipped = false;
        $orderState->delivery = false;
        $orderState->pdf_delivery = false;
        $orderState->pdf_invoice = false;
        $orderState->send_email = false;
        $orderState->hidden = false;
        $orderState->unremovable = true;
        $orderState->template = '';
        $orderState->deleted = false;

        if (!$orderState->add()) {
            $this->_errors[] = 'Nie udało się utworzyć stanu zamówienia COODY_OS_CODCARD_VALIDATION';
            return false;
        }

        Configuration::updateGlobalValue(self::CONFIG_OS_CODCARD_VALIDATION, (int) $orderState->id);

        $iconSource = $this->getLocalPath() . 'views/img/orderstate/COODY_OS_CODCARD_VALIDATION.gif';
        if (Tools::file_exists_cache($iconSource)) {
            @Tools::copy($iconSource, _PS_ORDER_STATE_IMG_DIR_ . (int) $orderState->id . '.gif');
        }

        return true;
    }
}
