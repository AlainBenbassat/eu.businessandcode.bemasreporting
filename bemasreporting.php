<?php

require_once 'bemasreporting.civix.php';

function bemasreporting_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'event') {
    $tasks[] = [
      'title' => 'Intekenlijst / Liste de présences',
      'class' => 'CRM_Bemasreporting_Task_ShowPresenceList',
    ];
  }
}

function bemasreporting_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userID) {
  $tasks['Participant']['participant_list'] = [
    'title' => 'Intekenlijst / Liste de présences',
    'icon' => 'fa-print',
    'redirect' => [
      'path' => "'civicrm/report/instance/61'",
      'query' => "{reset: 1}",
      'data' => "{participant_ids: ids.join(',')}",
    ],
  ];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function bemasreporting_civicrm_config(&$config) {
  _bemasreporting_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function bemasreporting_civicrm_install() {
  _bemasreporting_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function bemasreporting_civicrm_enable() {
  _bemasreporting_civix_civicrm_enable();
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

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
function bemasreporting_civicrm_navigationMenu(&$menu) {
  _bemasreporting_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'eu.businessandcode.bemasreporting')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _bemasreporting_civix_navigationMenu($menu);
} // */
