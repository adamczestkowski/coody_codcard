<?php
/**
 * Upgrade coody_codcard to 1.2.0 — płatność przelewem.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Coody_Codcard $module
 *
 * @return bool
 */
function upgrade_module_1_2_0($module)
{
    return $module->installOrderStateWire()
        && $module->installCurrenciesForAll();
}
