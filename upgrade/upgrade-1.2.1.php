<?php
/**
 * Upgrade coody_codcard to 1.2.1 — jeden mail, ładny blok konta, własny stan przelewu.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Coody_Codcard $module
 *
 * @return bool
 */
function upgrade_module_1_2_1($module)
{
    return $module->installOrderStateWire();
}
