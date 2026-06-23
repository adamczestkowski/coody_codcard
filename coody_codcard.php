<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Coody_Codcard extends PaymentModule
{
    const PAYMENT_TYPE_CARD = 'card';
    const PAYMENT_TYPE_WIRE = 'wire';

    const MAIL_TEMPLATE_WIRE = 'coody_codcard_wire';

    const HOOKS = [
        'displayOrderConfirmation',
        'paymentOptions',
        'sendMailAlterTemplateVars',
        'actionGetExtraMailTemplateVars',
        'actionValidateOrderAfter',
    ];

    const CONFIG_OS_CODCARD_VALIDATION = 'COODY_OS_CODCARD_VALIDATION';
    const CONFIG_OS_WIRE_VALIDATION = 'COODY_OS_WIRE_VALIDATION';
    const CONFIG_EXTRA_CONTENT = 'COODY_CODCARD_EXTRA_CONTENT';
    const CONFIG_ACCOUNTS = 'COODY_CODCARD_ACCOUNTS';

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

    public static $defaultWireIntroContent = [
        'pl' => 'Prosimy o przelew na kwotę zamówienia na konto wskazane poniżej. W tytule przelewu podaj numer zamówienia. Zamówienie zostanie zrealizowane po zaksięgowaniu wpłaty.',
        'en' => 'Please transfer the order amount to the account below. Include your order reference in the transfer description. Your order will be processed once payment is received.',
        'de' => 'Bitte überweisen Sie den Bestellbetrag auf das unten angegebene Konto. Geben Sie Ihre Bestellnummer als Verwendungszweck an. Die Bestellung wird nach Zahlungseingang bearbeitet.',
        'fr' => 'Veuillez virer le montant de la commande sur le compte ci-dessous. Indiquez votre numéro de commande en libellé. La commande sera traitée après réception du paiement.',
        'es' => 'Transfiera el importe del pedido a la cuenta indicada abajo. Indique su número de pedido en el concepto. El pedido se procesará tras recibir el pago.',
        'it' => 'Effettua un bonifico dell\'importo dell\'ordine sul conto indicato sotto. Inserisci il numero d\'ordine nella causale. L\'ordine verrà elaborato dopo la ricezione del pagamento.',
        'cs' => 'Prosíme o převod částky objednávky na účet uvedený níže. Do zprávy pro příjemce uveďte číslo objednávky. Objednávku zpracujeme po připsání platby.',
        'sk' => 'Prosíme o prevod sumy objednávky na účet uvedený nižšie. Do správy pre príjemcu uveďte číslo objednávky. Objednávku spracujeme po pripísaní platby.',
        'uk' => 'Будь ласка, перерахуйте суму замовлення на рахунок, зазначений нижче. У призначенні платежу вкажіть номер замовлення. Замовлення буде оброблено після зарахування коштів.',
        'ru' => 'Пожалуйста, переведите сумму заказа на счёт, указанный ниже. Укажите номер заказа в назначении платежа. Заказ будет обработан после поступления оплаты.',
    ];

    public static $defaultWireMailSubjects = [
        'pl' => 'Dane do przelewu — zamówienie %s',
        'en' => 'Wire transfer details — order %s',
        'de' => 'Überweisungsdaten — Bestellung %s',
        'fr' => 'Coordonnées bancaires — commande %s',
        'es' => 'Datos de transferencia — pedido %s',
        'it' => 'Dati bonifico — ordine %s',
        'cs' => 'Údaje k převodu — objednávka %s',
        'sk' => 'Údaje k prevodu — objednávka %s',
        'uk' => 'Дані для переказу — замовлення %s',
        'ru' => 'Данные для перевода — заказ %s',
    ];

    public function __construct()
    {
        $this->name = 'coody_codcard';
        $this->tab = 'payments_gateways';
        $this->author = 'Coody';
        $this->version = '1.3.1';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->controllers = ['validation'];
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Płatność kartą przy odbiorze i przelewem');
        $this->description = $this->l('Karta przy odbiorze oraz przelew bankowy z osobnym numerem konta dla każdej waluty.');
        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować moduł?');
    }

    public function install()
    {
        return parent::install()
            && (bool) $this->registerHook(static::HOOKS)
            && $this->installOrderState()
            && $this->installOrderStateWire()
            && $this->installDefaultExtraContent()
            && $this->installCurrenciesForAll();
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CONFIG_EXTRA_CONTENT);
        Configuration::deleteByName(self::CONFIG_ACCOUNTS);

        return parent::uninstall();
    }

    public function getCardPaymentLabel()
    {
        return $this->l('Płatność kartą przy odbiorze');
    }

    public function getWirePaymentLabel()
    {
        return $this->l('Płatność przelewem');
    }

    public function isWireOrder(Order $order)
    {
        return Validate::isLoadedObject($order) && $order->payment === $this->getWirePaymentLabel();
    }

    public function installDefaultExtraContent()
    {
        $values = [];
        foreach (Language::getLanguages(false) as $lang) {
            $iso = Tools::strtolower($lang['iso_code']);
            $values[(int) $lang['id_lang']] = self::$defaultExtraContent[$iso] ?? self::$defaultExtraContent['en'];
        }

        return Configuration::updateValue(self::CONFIG_EXTRA_CONTENT, $values, true);
    }

    public function installCurrenciesForAll()
    {
        $currencies = Currency::getCurrencies(false, true, true);
        if (empty($currencies)) {
            return true;
        }

        Db::getInstance()->delete('module_currency', 'id_module = ' . (int) $this->id);

        $rows = [];
        foreach ($currencies as $currency) {
            $rows[] = '(' . (int) $this->id . ', ' . (int) $currency['id_currency'] . ')';
        }

        return Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'module_currency` (`id_module`, `id_currency`) VALUES ' . implode(',', $rows)
        );
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $values = [];
            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $values[$idLang] = Tools::getValue(self::CONFIG_EXTRA_CONTENT . '_' . $idLang, '');
            }
            Configuration::updateValue(self::CONFIG_EXTRA_CONTENT, $values, true);

            $accounts = [];
            foreach (Currency::getCurrencies(false, true, true) as $currency) {
                $idCurrency = (int) $currency['id_currency'];
                $accounts[$idCurrency] = trim((string) Tools::getValue(self::CONFIG_ACCOUNTS . '_' . $idCurrency, ''));
            }
            $this->saveAccountsConfig($accounts);

            $output .= $this->displayConfirmation($this->l('Ustawienia zapisane.'));
        }

        return $output . $this->renderConfigForm();
    }

    /**
     * @return array<int, string>
     */
    public function getAccountsConfig()
    {
        $raw = Configuration::get(self::CONFIG_ACCOUNTS);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $accounts = [];
        foreach ($decoded as $idCurrency => $account) {
            $accounts[(int) $idCurrency] = trim((string) $account);
        }

        return $accounts;
    }

    /**
     * @param array<int, string> $accounts
     */
    public function saveAccountsConfig(array $accounts)
    {
        $normalized = [];
        foreach ($accounts as $idCurrency => $account) {
            $normalized[(int) $idCurrency] = trim((string) $account);
        }

        return Configuration::updateValue(self::CONFIG_ACCOUNTS, json_encode($normalized));
    }

    public function getAccountForCurrency($idCurrency)
    {
        $accounts = $this->getAccountsConfig();

        return $accounts[(int) $idCurrency] ?? '';
    }

    public function getExtraContent($idLang)
    {
        return $this->getLocalizedConfigValue(self::CONFIG_EXTRA_CONTENT, (int) $idLang, self::$defaultExtraContent);
    }

    public function getWireIntroContent($idLang)
    {
        $iso = Tools::strtolower((string) Language::getIsoById((int) $idLang));

        return self::$defaultWireIntroContent[$iso] ?? self::$defaultWireIntroContent['en'];
    }

    public function getWireMailSubject(Order $order)
    {
        $iso = Tools::strtolower((string) Language::getIsoById((int) $order->id_lang));
        $template = self::$defaultWireMailSubjects[$iso] ?? self::$defaultWireMailSubjects['en'];

        return sprintf($template, $order->reference);
    }

    /**
     * @param array<string, string> $defaultsByIso
     */
    private function getLocalizedConfigValue($configKey, $idLang, array $defaultsByIso)
    {
        $value = Configuration::get($configKey, (int) $idLang);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $fallback = Configuration::get($configKey, $defaultLang);
        if (is_string($fallback) && $fallback !== '') {
            return $fallback;
        }

        $iso = Tools::strtolower((string) Language::getIsoById((int) $idLang));

        return $defaultsByIso[$iso] ?? $defaultsByIso['en'];
    }

    /**
     * @return array<string, string>
     */
    public function getMailTemplateVars($idCurrency)
    {
        return $this->buildWireOrderMailVarsFromAccount(
            $this->getAccountForCurrency((int) $idCurrency),
            (int) $idCurrency
        );
    }

    /**
     * @return array<string, string>
     */
    public function buildWireOrderMailVars(Order $order)
    {
        if (!$this->isWireOrder($order)) {
            return $this->emptyWireMailVars();
        }

        $currency = new Currency((int) $order->id_currency);

        return $this->buildWireOrderMailVarsFromAccount(
            $this->getAccountForCurrency((int) $order->id_currency),
            (int) $order->id_currency,
            Tools::getContextLocale($this->context)->formatPrice(
                $order->getOrdersTotalPaid(),
                $currency->iso_code
            ),
            $order->reference
        );
    }

    /**
     * @return array<string, string>
     */
    public function getValidateOrderExtraVars($idCurrency, $paymentType = self::PAYMENT_TYPE_CARD)
    {
        return $this->emptyWireMailVars();
    }

    /**
     * @return array<string, string>
     */
    private function emptyWireMailVars()
    {
        return [
            '{coody_codcard_account}' => '',
            '{coody_codcard_account_html}' => '',
            '{coody_codcard_account_text}' => '',
            '{coody_codcard_wire_payment_html}' => '',
            '{coody_codcard_wire_payment_txt}' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildWireOrderMailVarsFromAccount($account, $idCurrency, $total = '', $reference = '')
    {
        $account = trim((string) $account);
        if ($account === '') {
            return $this->emptyWireMailVars();
        }

        $currency = new Currency((int) $idCurrency);
        $safeAccount = Tools::safeOutput($account);
        $labelAccount = $this->l('Numer konta');
        $labelAmount = $this->l('Kwota do przelewu');
        $labelReference = $this->l('Tytuł przelewu');
        $iso = Tools::safeOutput($currency->iso_code);

        $html = '<table role="presentation" width="100%" style="margin-top:12px;border:1px solid #DFDFDF;border-radius:4px;background:#F8F9FA;">'
            . '<tr><td style="padding:16px 20px;font-family:Open sans,arial,sans-serif;font-size:14px;line-height:22px;color:#363A41;">'
            . '<p style="margin:0 0 10px;"><strong>' . $labelAmount . ':</strong> ' . Tools::safeOutput((string) $total) . '</p>'
            . '<p style="margin:0 0 10px;"><strong>' . $labelAccount . ' (' . $iso . '):</strong><br>' . nl2br($safeAccount) . '</p>'
            . '<p style="margin:0;"><strong>' . $labelReference . ':</strong> ' . Tools::safeOutput((string) $reference) . '</p>'
            . '</td></tr></table>';

        $txt = "\n\n" . $labelAmount . ': ' . $total
            . "\n" . $labelAccount . ' (' . $currency->iso_code . '): ' . $account
            . "\n" . $labelReference . ': ' . $reference . "\n";

        return [
            '{coody_codcard_account}' => $account,
            '{coody_codcard_account_html}' => '',
            '{coody_codcard_account_text}' => '',
            '{coody_codcard_wire_payment_html}' => $html,
            '{coody_codcard_wire_payment_txt}' => $txt,
        ];
    }

    /**
     * @param array{cart: Cart, order: Order|null, customer: Customer, currency: Currency, orderStatus: OrderState} $params
     */
    public function hookActionValidateOrderAfter(array $params)
    {
        if (empty($params['order']) || !($params['order'] instanceof Order)) {
            return;
        }

        $this->sendWirePaymentEmail($params['order']);
    }

    /**
     * @return bool
     */
    public function sendWirePaymentEmail(Order $order)
    {
        if (!Validate::isLoadedObject($order) || $order->module !== $this->name || !$this->isWireOrder($order)) {
            return false;
        }

        if ($this->getAccountForCurrency((int) $order->id_currency) === '') {
            return false;
        }

        $customer = new Customer((int) $order->id_customer);
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            return false;
        }

        $currency = new Currency((int) $order->id_currency);
        $idLang = (int) $order->id_lang;
        $idShop = (int) $order->id_shop;
        $total = Tools::getContextLocale($this->context)->formatPrice(
            $order->getOrdersTotalPaid(),
            $currency->iso_code
        );

        $data = array_merge(
            [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
                '{order_name}' => $order->reference,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME', null, null, $idShop),
                '{shop_url}' => $this->context->link->getPageLink('index', true, $idLang, null, false, $idShop),
                '{total_paid}' => $total,
                '{coody_codcard_account}' => $this->getAccountForCurrency((int) $order->id_currency),
                '{coody_codcard_account_html}' => nl2br(Tools::safeOutput($this->getAccountForCurrency((int) $order->id_currency))),
                '{coody_codcard_currency_iso}' => $currency->iso_code,
                '{history_url}' => $this->context->link->getPageLink('history', true, $idLang, null, false, $idShop),
                '{guest_tracking_url}' => $this->context->link->getPageLink('guest-tracking', true, $idLang, null, false, $idShop),
            ],
            $this->buildWireOrderMailVars($order)
        );

        return (bool) Mail::Send(
            $idLang,
            self::MAIL_TEMPLATE_WIRE,
            $this->getWireMailSubject($order),
            $data,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            $this->local_path . 'mails/',
            false,
            $idShop
        );
    }

    public function hookSendMailAlterTemplateVars(array $params)
    {
        if (empty($params['template_vars']) || !is_array($params['template_vars'])) {
            return;
        }

        $template = isset($params['template']) ? (string) $params['template'] : '';
        $this->injectMailTemplateVars($params['template_vars'], $template);
    }

    public function hookActionGetExtraMailTemplateVars(array $params)
    {
        if (empty($params['extra_template_vars']) || !is_array($params['extra_template_vars'])) {
            return;
        }

        $templateVars = isset($params['template_vars']) && is_array($params['template_vars'])
            ? $params['template_vars']
            : [];

        $order = $this->resolveOrderFromMailTemplateVars($templateVars);
        if (!$order || $order->module !== $this->name || !$this->isWireOrder($order)) {
            return;
        }

        foreach ($this->buildWireOrderMailVars($order) as $key => $value) {
            $params['extra_template_vars'][$key] = $value;
        }

        $this->injectBankwireTemplateVars($params['extra_template_vars'], $order);
    }

    /**
     * @param array<string, mixed> $templateVars
     */
    private function injectMailTemplateVars(array &$templateVars, $template = '')
    {
        $order = $this->resolveOrderFromMailTemplateVars($templateVars);
        if (!$order || $order->module !== $this->name || !$this->isWireOrder($order)) {
            return;
        }

        foreach ($this->buildWireOrderMailVars($order) as $key => $value) {
            $templateVars[$key] = $value;
        }

        if ($template === 'bankwire') {
            $this->injectBankwireTemplateVars($templateVars, $order);
        }
    }

    /**
     * @param array<string, mixed> $templateVars
     */
    private function injectBankwireTemplateVars(array &$templateVars, Order $order)
    {
        $account = $this->getAccountForCurrency((int) $order->id_currency);
        if ($account === '') {
            return;
        }

        $currency = new Currency((int) $order->id_currency);
        $total = Tools::getContextLocale($this->context)->formatPrice(
            $order->getOrdersTotalPaid(),
            $currency->iso_code
        );

        $templateVars['{bankwire_owner}'] = Configuration::get('PS_SHOP_NAME');
        $templateVars['{bankwire_details}'] = nl2br(Tools::safeOutput($account))
            . '<br><br><strong>' . $this->l('Tytuł przelewu') . ':</strong> ' . Tools::safeOutput($order->reference);
        $templateVars['{bankwire_address}'] = '';
        $templateVars['{total_paid}'] = $total;
    }

    /**
     * @param array<string, mixed> $templateVars
     *
     * @return Order|null
     */
    private function resolveOrderFromMailTemplateVars(array $templateVars)
    {
        if (!empty($templateVars['{id_order}'])) {
            $order = new Order((int) $templateVars['{id_order}']);
            if (Validate::isLoadedObject($order)) {
                return $order;
            }
        }

        if (!empty($templateVars['{order_name}'])) {
            $orders = Order::getByReference((string) $templateVars['{order_name}']);
            if ($orders && $orders->count()) {
                $order = $orders->getFirst();
                if (Validate::isLoadedObject($order)) {
                    return $order;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getHelperFormLanguages($defaultLangId)
    {
        if ($this->context->controller instanceof AdminController) {
            return $this->context->controller->getLanguages();
        }

        $languages = [];
        foreach (Language::getLanguages(false) as $lang) {
            $languages[] = [
                'id_lang' => (int) $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => (int) ((int) $lang['id_lang'] === (int) $defaultLangId),
            ];
        }

        return $languages;
    }

    private function renderConfigForm()
    {
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
        $languages = $this->getHelperFormLanguages($defaultLangId);
        $currencies = Currency::getCurrencies(false, true, true);
        $accounts = $this->getAccountsConfig();

        $accountInputs = [];
        foreach ($currencies as $currency) {
            $idCurrency = (int) $currency['id_currency'];
            $accountInputs[] = [
                'type' => 'textarea',
                'label' => sprintf('%s (%s)', $currency['name'], $currency['iso_code']),
                'name' => self::CONFIG_ACCOUNTS . '_' . $idCurrency,
                'cols' => 60,
                'rows' => 3,
                'desc' => $this->l('Dane konta do przelewu (IBAN, bank, właściciel) widoczne przy płatności przelewem dla tej waluty.'),
            ];
        }

        $fields_form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Płatność kartą przy odbiorze'),
                        'icon' => 'icon-credit-card',
                    ],
                    'input' => [
                        [
                            'type' => 'textarea',
                            'label' => $this->l('Treść przy opcji płatności'),
                            'name' => self::CONFIG_EXTRA_CONTENT,
                            'lang' => true,
                            'cols' => 60,
                            'rows' => 5,
                            'autoload_rte' => true,
                            'desc' => $this->l('Informacja wyświetlana przy opcji płatności kartą przy odbiorze.'),
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Zapisz'),
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Płatność przelewem — numery kont wg walut'),
                        'icon' => 'icon-bank',
                    ],
                    'description' => $this->l('Osobne konto dla każdej waluty sklepu. Opcja przelewu pojawi się w checkout tylko wtedy, gdy konto dla bieżącej waluty jest uzupełnione.'),
                    'input' => $accountInputs,
                    'submit' => [
                        'title' => $this->l('Zapisz'),
                        'class' => 'btn btn-default pull-right',
                    ],
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
            $iso = Tools::strtolower($lang['iso_code']);

            $stored = Configuration::get(self::CONFIG_EXTRA_CONTENT, $idLang);
            if (!is_string($stored) || $stored === '') {
                $stored = self::$defaultExtraContent[$iso] ?? self::$defaultExtraContent['en'];
            }
            $fieldsValue[self::CONFIG_EXTRA_CONTENT][$idLang] = $stored;
        }

        foreach ($currencies as $currency) {
            $idCurrency = (int) $currency['id_currency'];
            $fieldsValue[self::CONFIG_ACCOUNTS . '_' . $idCurrency] = $accounts[$idCurrency] ?? '';
        }

        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $languages,
            'id_language' => $defaultLangId,
        ];

        return $helper->generateForm($fields_form);
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

        if (!$this->checkCurrency($cart)) {
            return [];
        }

        $options = [];
        $idLang = (int) $this->context->language->id;
        $idCurrency = (int) $cart->id_currency;

        if (!$cart->isVirtualCart()) {
            $this->context->smarty->assign([
                'coody_codcard_extra_content' => $this->getExtraContent($idLang),
            ]);

            $cardOption = new PaymentOption();
            $cardOption->setModuleName($this->name);
            $cardOption->setCallToActionText($this->getCardPaymentLabel());
            $cardOption->setAction($this->context->link->getModuleLink(
                $this->name,
                'validation',
                ['type' => self::PAYMENT_TYPE_CARD],
                true
            ));
            $cardOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/card-on-delivery.svg'));
            $cardOption->setAdditionalInformation($this->fetch('module:coody_codcard/views/templates/hook/paymentOptions-additionalInformation.tpl'));
            $options[] = $cardOption;
        }

        $account = $this->getAccountForCurrency($idCurrency);
        if ($account !== '') {
            $currency = new Currency($idCurrency);
            $this->context->smarty->assign([
                'coody_codcard_wire_intro_content' => $this->getWireIntroContent($idLang),
                'coody_codcard_account' => $account,
                'coody_codcard_currency_iso' => $currency->iso_code,
                'coody_codcard_total' => $this->context->getCurrentLocale()->formatPrice(
                    $cart->getOrderTotal(true, Cart::BOTH),
                    $currency->iso_code
                ),
            ]);

            $wireOption = new PaymentOption();
            $wireOption->setModuleName($this->name);
            $wireOption->setCallToActionText($this->getWirePaymentLabel());
            $wireOption->setAction($this->context->link->getModuleLink(
                $this->name,
                'validation',
                ['type' => self::PAYMENT_TYPE_WIRE],
                true
            ));
            $wireOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/bank-transfer.svg'));
            $wireOption->setAdditionalInformation($this->fetch('module:coody_codcard/views/templates/hook/paymentOptions-wire-additionalInformation.tpl'));
            $options[] = $wireOption;
        }

        return $options;
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

        $currency = new Currency((int) $order->id_currency);
        $isWire = $this->isWireOrder($order);

        $this->context->smarty->assign([
            'shop_name' => $this->context->shop->name,
            'total' => $this->context->getCurrentLocale()->formatPrice(
                $order->getOrdersTotalPaid(),
                $currency->iso_code
            ),
            'reference' => $order->reference,
            'contact_url' => $this->context->link->getPageLink('contact', true),
            'coody_codcard_account' => $isWire ? $this->getAccountForCurrency((int) $order->id_currency) : '',
            'coody_codcard_currency_iso' => $currency->iso_code,
        ]);

        $template = $isWire
            ? 'module:coody_codcard/views/templates/hook/displayOrderConfirmation-wire.tpl'
            : 'module:coody_codcard/views/templates/hook/displayOrderConfirmation.tpl';

        return $this->fetch($template);
    }

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

    public function installOrderState()
    {
        return $this->installCustomOrderState(
            self::CONFIG_OS_CODCARD_VALIDATION,
            [
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
            ],
            '#34219E',
            'COODY_OS_CODCARD_VALIDATION.gif'
        );
    }

    public function installOrderStateWire()
    {
        return $this->installCustomOrderState(
            self::CONFIG_OS_WIRE_VALIDATION,
            [
                'pl' => 'Oczekiwanie na przelew',
                'en' => 'Awaiting bank wire payment',
                'de' => 'Warten auf Überweisung',
                'fr' => 'En attente de virement',
                'es' => 'Esperando transferencia bancaria',
                'it' => 'In attesa di bonifico bancario',
                'cs' => 'Čeká se na bankovní převod',
                'sk' => 'Čaká sa na bankový prevod',
                'uk' => 'Очікується банківський переказ',
                'ru' => 'Ожидается банковский перевод',
            ],
            '#4169E1',
            'COODY_OS_WIRE_VALIDATION.gif'
        );
    }

    /**
     * @param array<string, string> $namesByIso
     */
    private function installCustomOrderState($configKey, array $namesByIso, $color, $iconFile)
    {
        $existingId = (int) Configuration::getGlobalValue($configKey);
        if ($existingId) {
            $existing = new OrderState($existingId);
            if (Validate::isLoadedObject($existing) && $existing->module_name === $this->name) {
                return true;
            }
        }

        $nameByLangId = [];
        foreach (Language::getLanguages(false) as $lang) {
            $iso = Tools::strtolower($lang['iso_code']);
            $nameByLangId[(int) $lang['id_lang']] = $namesByIso[$iso] ?? $namesByIso['en'];
        }

        $orderState = new OrderState();
        $orderState->module_name = $this->name;
        $orderState->name = $nameByLangId;
        $orderState->color = $color;
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
            $this->_errors[] = 'Nie udało się utworzyć stanu zamówienia ' . $configKey;

            return false;
        }

        Configuration::updateGlobalValue($configKey, (int) $orderState->id);

        $iconSource = $this->getLocalPath() . 'views/img/orderstate/' . $iconFile;
        if (Tools::file_exists_cache($iconSource)) {
            @Tools::copy($iconSource, _PS_ORDER_STATE_IMG_DIR_ . (int) $orderState->id . '.gif');
        }

        return true;
    }
}
