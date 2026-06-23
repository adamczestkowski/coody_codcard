<?php

class Coody_CodcardValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /** @var PaymentModule */
    public $module;

    public function postProcess()
    {
        $paymentType = (string) Tools::getValue('type', Coody_Codcard::PAYMENT_TYPE_CARD);
        if (!in_array($paymentType, [Coody_Codcard::PAYMENT_TYPE_CARD, Coody_Codcard::PAYMENT_TYPE_WIRE], true)) {
            $paymentType = Coody_Codcard::PAYMENT_TYPE_CARD;
        }

        if (!$this->checkIfContextIsValid($paymentType) || !$this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                ['step' => 1]
            ));
        }

        $customer = new Customer((int) $this->context->cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                ['step' => 1]
            ));
        }

        $idCurrency = (int) $this->context->cart->id_currency;

        if ($paymentType === Coody_Codcard::PAYMENT_TYPE_WIRE) {
            if ($this->module->getAccountForCurrency($idCurrency) === '') {
                Tools::redirect($this->context->link->getPageLink(
                    'order',
                    true,
                    (int) $this->context->language->id,
                    ['step' => 1]
                ));
            }

            $orderStateId = (int) Configuration::getGlobalValue(Coody_Codcard::CONFIG_OS_WIRE_VALIDATION);
            if (!$orderStateId) {
                $orderStateId = (int) Configuration::get('PS_OS_BANKWIRE');
            }
            $paymentLabel = $this->module->getWirePaymentLabel();
            $extraVars = $this->module->getValidateOrderExtraVars($idCurrency, Coody_Codcard::PAYMENT_TYPE_WIRE);
        } else {
            $orderStateId = (int) Configuration::getGlobalValue(Coody_Codcard::CONFIG_OS_CODCARD_VALIDATION);
            if (!$orderStateId) {
                $orderStateId = (int) Configuration::get('PS_OS_PREPARATION');
            }
            $paymentLabel = $this->module->getCardPaymentLabel();
            $extraVars = [];
        }

        $this->module->validateOrder(
            (int) $this->context->cart->id,
            $orderStateId,
            (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
            $paymentLabel,
            null,
            $extraVars,
            $idCurrency,
            false,
            $customer->secure_key
        );

        Tools::redirect($this->context->link->getPageLink(
            'order-confirmation',
            true,
            (int) $this->context->language->id,
            [
                'id_cart' => (int) $this->context->cart->id,
                'id_module' => (int) $this->module->id,
                'id_order' => (int) $this->module->currentOrder,
                'key' => $customer->secure_key,
            ]
        ));
    }

    /**
     * @param string $paymentType
     */
    private function checkIfContextIsValid($paymentType)
    {
        $valid = Validate::isLoadedObject($this->context->cart)
            && Validate::isUnsignedInt($this->context->cart->id_customer)
            && Validate::isUnsignedInt($this->context->cart->id_address_invoice);

        if (!$valid) {
            return false;
        }

        if ($paymentType === Coody_Codcard::PAYMENT_TYPE_WIRE) {
            if ($this->context->cart->isVirtualCart()) {
                return true;
            }

            return Validate::isUnsignedInt($this->context->cart->id_address_delivery);
        }

        return Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && !$this->context->cart->isVirtualCart();
    }

    /**
     * Upewnia się, że metoda jest nadal dostępna (np. po zmianie adresu).
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = Module::getPaymentModules();
        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }
}
