<?php
/*-------------------------------------------------------+
| Komptenzzentrum Webshop (Magento) Anbindung            |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'magento.civix.php';
use CRM_Magento_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function magento_civicrm_config(&$config) {
  _magento_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function magento_civicrm_install() {
  _magento_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function magento_civicrm_enable() {
  _magento_civix_civicrm_enable();

  // make sure our custom fields exist
  require_once 'CRM/Magento/CustomData.php';
  $customData = new CRM_Magento_CustomData('net.kompetenzz.civi.magento');
  $customData->syncOptionGroup(__DIR__ . '/resources/option_group_activity_type.json');
  $customData->syncCustomGroup(__DIR__ . '/resources/custom_group_wsorder.json');
  $customData->syncCustomGroup(__DIR__ . '/resources/custom_group_relationship.json');
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function magento_civicrm_navigationMenu(&$menu) {
  _magento_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _magento_civix_navigationMenu($menu);
} // */
