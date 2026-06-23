<?php
/**
 * Upgrade coody_codcard to 1.3.0 — osobny mail z danymi do przelewu.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Coody_Codcard $module
 *
 * @return bool
 */
function upgrade_module_1_3_0($module)
{
    return $module->registerHook('actionValidateOrderAfter');
}
