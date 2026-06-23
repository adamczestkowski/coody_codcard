<?php
/**
 * Upgrade coody_codcard to 1.1.0 — numery kont per waluta.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Coody_Codcard $module
 *
 * @return bool
 */
function upgrade_module_1_1_0($module)
{
    return $module->registerHook('sendMailAlterTemplateVars')
        && $module->registerHook('actionGetExtraMailTemplateVars')
        && $module->installCurrenciesForAll();
}
