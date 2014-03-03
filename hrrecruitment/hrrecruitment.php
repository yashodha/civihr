<?php

require_once 'hrrecruitment.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function hrrecruitment_civicrm_config(&$config) {
  _hrrecruitment_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function hrrecruitment_civicrm_xmlMenu(&$files) {
  _hrrecruitment_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function hrrecruitment_civicrm_install() {
  $reportWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Reports', 'weight', 'name');

  $vacancyNavigation = new CRM_Core_DAO_Navigation();
  $params = array (
    'domain_id'  => CRM_Core_Config::domainID(),
    'label'      => 'Vacancies',
    'name'       => 'Vacancies',
    'url'        => null,
    'operator'   => null,
    'weight'     => $reportWeight-1,
    'is_active'  => 1
  );
  $vacancyNavigation->copyValues($params);
  $vacancyNavigation->save();

  $vacancyMenuTree = array(
    array(
      'label' => ts('Dashboard'),
      'name' => 'dashboard',
      'url'  =>  null,
      'permission' => null,
    ),
    array(
      'label' => ts('New Vacancy'),
      'name' => 'new_vacancy',
      'url'  => null,
      'permission' => null,
    ),
    array(
      'label' => ts('New Template'),
      'name' => 'new_template',
      'url'  => null,
      'permission' => null,
    ),
    array(
      'label'      => ts('Find Vacancies'),
      'name'       => 'find_vacancies',
      'url'        => null,
      'permission' => null,
    ),
    array(
      'label'      => ts('Reports'),
      'name'       => 'reports',
      'url'        => null,
      'permission' => null,
    ),
  );

  foreach ($vacancyMenuTree as $key => $menuItems) {
    $menuItems['is_active'] = 1;
    $menuItems['parent_id'] = $vacancyNavigation->id;
    $menuItems['weight'] = $key;
    CRM_Core_BAO_Navigation::add($menuItems);
  }
  CRM_Core_BAO_Navigation::resetNavigation();

  return _hrrecruitment_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function hrrecruitment_civicrm_uninstall() {
  return _hrrecruitment_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function hrrecruitment_civicrm_enable() {
  return _hrrecruitment_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function hrrecruitment_civicrm_disable() {
  return _hrrecruitment_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function hrrecruitment_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _hrrecruitment_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function hrrecruitment_civicrm_managed(&$entities) {
  return _hrrecruitment_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 */
function hrrecruitment_civicrm_caseTypes(&$caseTypes) {
  _hrrecruitment_civix_civicrm_caseTypes($caseTypes);
}

function hrrecruitment_civicrm_navigationMenu( &$params ) {
  $vacanciesMenuItems = array();
  $vacanciesType = array("Draft", "Open", "Closed", "Rejected", "Cancelled" );
  $vacanciesId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Vacancies', 'id', 'name');
  $newVacanciesId =  CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'new_vacancy', 'id', 'name');
  $count = 0;
  foreach ($vacanciesType as $cTypeId => $vacanciesTypeName) {
    $vacanciesMenuItems[$count] = array(
      'attributes' => array(
        'label'      => "{$vacanciesTypeName}",
        'name'       => "{$vacanciesTypeName}",
        'url'        => NULL,
        'permission' => NULL,
        'operator'   => 'OR',
        'separator'  => NULL,
        'parentID'   => $newVacanciesId,
        'navID'      => 1,
        'active'     => 1
      )
    );
    $count++;
  }
  if (!empty($vacanciesMenuItems)) {
    $params[$vacanciesId]['child'][$newVacanciesId]['child'] = $vacanciesMenuItems;
  }
}