<?php

require_once 'hrabsence.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function hrabsence_civicrm_config(&$config) {
  _hrabsence_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function hrabsence_civicrm_xmlMenu(&$files) {
  _hrabsence_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function hrabsence_civicrm_install() {
  $reportWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Reports', 'weight', 'name');

  $absenceNavigation = new CRM_Core_DAO_Navigation();
  $params = array (
    'domain_id'  => CRM_Core_Config::domainID(),
    'label'      => 'Absences',
    'name'       => 'Absences',
    'url'        => null,
    'permission' => 'access HRAbsences',
    'operator'   => null,
    'weight'     => $reportWeight-1,
    'is_active'  => 1
  );
  $absenceNavigation->copyValues($params);
  $absenceNavigation->save();

  $absenceMenuTree = array(
    array(
      'label' => ts('My Absences'),
      'name' => 'my_absences',
      'url'  => 'civicrm/absences',
    ),
    array(
      'label' => ts('Calendar'),
      'name' => 'calendar',
      'url'  => null,
    ),
    array(
      'label' => ts('New Absence'),
      'name' => 'new_absence',
      'url'  => null,
    ),
    array(
      'label'      => 'Absence Report',
      'name'       => 'absence_report',
      'url'        => 'civicrm/report/list?grp=Absence&reset=1',
    ),
    array(
      'label'      => 'Manage Entitlements',
      'name'       => 'manage_entitlements',
      'url'        =>  null,
    ),
    array(
      'label'      => 'Absence Types',
      'name'       => 'absenceTypes',
      'url'        => 'civicrm/absence/type?reset=1',
    ),
  );

  foreach ($absenceMenuTree as $key => $menuItems) {
    $menuItems['has_separator'] = $menuItems['is_active'] = 1;
    $menuItems['parent_id'] = $absenceNavigation->id;
    $menuItems['weight'] = $key;
    CRM_Core_BAO_Navigation::add($menuItems);
  }
  CRM_Core_BAO_Navigation::resetNavigation();
  return _hrabsence_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function hrabsence_civicrm_uninstall() {
  $absencesId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Absences', 'id', 'name');
  CRM_Core_BAO_Navigation::processDelete($absencesId);
  CRM_Core_BAO_Navigation::resetNavigation();
  return _hrabsence_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function hrabsence_civicrm_enable() {
  CRM_Core_BAO_Navigation::processUpdate(array('name' => 'Absences'), array('is_active' => 1));
  CRM_Core_BAO_Navigation::resetNavigation();
  return _hrabsence_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function hrabsence_civicrm_disable() {
  CRM_Core_BAO_Navigation::processUpdate(array('name' => 'Absences'), array('is_active' => 0));
  CRM_Core_BAO_Navigation::resetNavigation();
  return _hrabsence_civix_civicrm_disable();
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
function hrabsence_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _hrabsence_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function hrabsence_civicrm_managed(&$entities) {
  return _hrabsence_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 */
function hrabsence_civicrm_caseTypes(&$caseTypes) {
  _hrabsence_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function myext_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _myext_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_entityTypes
 */
function hrabsence_civicrm_entityTypes(&$entityTypes) {
  $entityTypes[] = array(
    'name' => 'HRAbsenceType',
    'class' => 'CRM_HRAbsence_DAO_HRAbsenceType',
    'table' => 'civicrm_absence_type',
  );
   $entityTypes[] = array(
    'name' => 'HRAbsencePeriod',
    'class' => 'CRM_HRAbsence_DAO_HRAbsencePeriod',
    'table' => 'civicrm_absence_period',
  );
  $entityTypes[] = array(
    'name' => 'HRAbsenceEntitlement',
    'class' => 'CRM_HRAbsence_DAO_HRAbsenceEntitlement',
    'table' => 'civicrm_absence_entitlement',
  );
}

/**
 * Implementation of hook_civicrm_alterFilters
 *
 * @param array $wrappers list of API_Wrapper instances
 * @param array $apiRequest
 */
function hrabsence_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  $action = strtolower($apiRequest['action']);
  if (strtolower($apiRequest['entity']) == 'activity' && ($action == 'get' || $action == 'getabsences')) {
    $wrappers[] = new CRM_HRAbsence_AbsenceRangeOption();
  }
}


function hrabsence_civicrm_navigationMenu( &$params ) {
  $absenceMenuItems = array();
  $absenceType = CRM_HRAbsence_BAO_HRAbsenceType::getActivityTypes();
  $absenceId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Absences', 'id', 'name');
  $newAbsenceId =  CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'new_absence', 'id', 'name');
  $count = 0;
  foreach ($absenceType as $aTypeId => $absenceTypeName) {
    $absenceMenuItems[$count] = array(
      'attributes' => array(
        'label'      => "{$absenceTypeName}",
        'name'       => "{$absenceTypeName}",
        'url'        => "civicrm/absence/set?atype={$aTypeId}&action=add",
        'permission' => 'access HRAbsences',
        'operator'   => NULL,
        'separator'  => NULL,
        'parentID'   => $newAbsenceId,
        'navID'      => 1,
        'active'     => 1
      )
    );
    $count++;
  }
  if (!empty($absenceMenuItems)) {
    $params[$absenceId]['child'][$newAbsenceId]['child'] = $absenceMenuItems;
  }
  $calendarReportId = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance', 'civihr/absence/calendar', 'id', 'report_id');
  $calendarId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'calendar', 'id', 'name');
  if ($calendarReportId) {
    $params[$absenceId]['child'][$calendarId]['attributes']['url'] = "civicrm/report/instance/{$calendarReportId}?reset=1";
  }
  else {
    $params[$absenceId]['child'][$calendarId]['attributes']['active'] = 0;
  }
}

function hrabsence_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Activity_Form_Activity') {
    $activityTypeId = $form->_activityTypeId;
    $activityId = $form->_activityId;
    $currentlyViewedContactId = $form->_currentlyViewedContactId;
    $paramsAbsenceType = array(
      'version' => 3,
      'sequential' => 1,
    );
    $resultAbsenceType = civicrm_api('HRAbsenceType', 'get', $paramsAbsenceType);
    $absenceType =  array();
    foreach ($resultAbsenceType['values'] as $key => $val) {
      $absenceType[$val['id']] = $val['debit_activity_type_id'];
    }

    if ( in_array($activityTypeId, $absenceType)) {
      if ($form->_action == CRM_Core_Action::VIEW) {
        $urlPathView = CRM_Utils_System::url('civicrm/absence/set', "atype={$activityTypeId}&aid={$activityId}&cid={$currentlyViewedContactId}&action=view&context=search&reset=1");
        CRM_Utils_System::redirect($urlPathView);
      }

      if ($form->_action == CRM_Core_Action::UPDATE) {
        $urlPathEdit = CRM_Utils_System::url('civicrm/absence/set', "atype={$activityTypeId}&aid={$activityId}&cid={$currentlyViewedContactId}&action=update&context=search&reset=1");
        CRM_Utils_System::redirect($urlPathEdit);
      }
    }
  }
}
