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
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function bemasreporting_civicrm_uninstall() {
  _bemasreporting_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function bemasreporting_civicrm_disable() {
  _bemasreporting_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function bemasreporting_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _bemasreporting_civix_civicrm_upgrade($op, $queue);
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

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function bemasreporting_civicrm_postInstall() {
  _bemasreporting_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function bemasreporting_civicrm_entityTypes(&$entityTypes) {
  _bemasreporting_civix_civicrm_entityTypes($entityTypes);
}
