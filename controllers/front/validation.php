<?php

class Coody_CodcardValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /** @var PaymentModule */
    public $module;

    public function postProcess()
    {
        if (!$this->checkIfContextIsValid() || !$this->checkIfPaymentOptionIsAvailable()) {
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

        $orderStateId = (int) Configuration::getGlobalValue(Coody_Codcard::CONFIG_OS_CODCARD_VALIDATION);
        if (!$orderStateId) {
            $orderStateId = (int) Configuration::get('PS_OS_PREPARATION');
        }

        $this->module->validateOrder(
            (int) $this->context->cart->id,
            $orderStateId,
            (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            [],
            (int) $this->context->currency->id,
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
     * Sprawdza, czy kontekst jest poprawny (koszyk, klient, adresy, nie-wirtualny).
     */
    private function checkIfContextIsValid()
    {
        return Validate::isLoadedObject($this->context->cart)
            && Validate::isUnsignedInt($this->context->cart->id_customer)
            && Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && Validate::isUnsignedInt($this->context->cart->id_address_invoice)
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
