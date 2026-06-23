<?php
/**
 * Upgrade coody_codcard to 1.2.2 — usunięcie treści przy przelewie.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Coody_Codcard $module
 *
 * @return bool
 */
function upgrade_module_1_2_2($module)
{
    return Configuration::deleteByName('COODY_CODCARD_WIRE_EXTRA_CONTENT');
}
