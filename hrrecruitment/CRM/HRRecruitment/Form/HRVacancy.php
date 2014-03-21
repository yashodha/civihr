<?php

/*
  +--------------------------------------------------------------------+
  | CiviHR version 1.3                                                 |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for Add/Edit Vacancy
 *
 */
class CRM_HRRecruitment_Form_HRVacancy extends CRM_Core_Form {

/**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->_isTemplate = (boolean) CRM_Utils_Request::retrieve('template', 'Integer', $this);
    $this->assign('isTemplate', $this->_isTemplate);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this);
  }

 /**
   * This function sets the default values for the form. For add/edit mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = array();
    $id = $this->_id;

    if ($id || ($id = CRM_Utils_Request::retrieve('template_id', 'Integer', $this))) {

      if (CRM_Utils_Request::retrieve('template_id', 'Integer', $this)) {
        $defaults['template_id'] = $id;
      }

      $result = civicrm_api3('HRVacancy', 'get', array('id' => $id));
      $defaults = $result['values'][$id];

      //format vacancy start/end date
      list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['start_date'], 'activityDateTime');
      list($defaults['end_date'], $defaults['end_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['end_date'], 'activityDateTime');

      if (!$this->_isTemplate) {
        $defaults['stages'] = array();
        $dao = new CRM_HRRecruitment_DAO_HRVacancyStage();
        $dao->vacancy_id = $id;
        $dao->find();
        while($dao->fetch()) {
          $defaults['stages'][$dao->weight] = $dao->case_status_id;
        }
      }

      $dao = new CRM_HRRecruitment_DAO_HRVacancyPermission();
      $dao->vacancy_id = $id;
      $dao->find();
      $count = 1;
      while ($dao->fetch()) {
        $defaults['permission'][$count] = $dao->permission;
        $defaults['permission_contact_id'][$count] = $dao->contact_id;
        $count++;
      }
      $count--;

      //show that only number of permission row(s) which have defaults if any
      if ($count) {
        $this->assign('showPermissionRow', $count);
      }

      foreach (array('application_profile', 'evaluation_profile') as $profileName) {
        $dao = new CRM_Core_DAO_UFJoin;
        $dao->module = 'Vacancy';
        $dao->entity_id = $id;
        $dao->module_data = $profileName;
        $dao->find(TRUE);
        $defaults[$profileName] = $dao->uf_group_id;
      }

      return $defaults;
    }
    else {
      foreach (array('application_profile', 'evaluation_profile') as $profileName) {
        if ($id = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileName, 'id', 'name')) {
          $defaults[$profileName] = $id;
        }
      }
      $defaults['status_id'] = CRM_Core_OptionGroup::getDefaultValue('vacancy_status');
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_HRRecruitment_DAO_HRVacancy');

    $templates  = $vacancyPermissions = array();

    $this->add('text', 'position', ts('Job Position'), $attributes['position'], TRUE);
    $this->addSelect('location', array('label' => ts('Location'), 'entity' => 'HRJob', 'field' => 'location'));
    $this->add('text', 'salary', ts('Salary'), $attributes['salary']);
    $this->addWysiwyg('description', ts('Description'), array('rows' => 2, 'cols' => 40));
    $this->addWysiwyg('benefits', ts('Benefits'), array('rows' => 2, 'cols' => 40));
    $this->addWysiwyg('requirements', ts('Requirements'),  array('rows' => 2, 'cols' => 40));
    $this->addDateTime('start_date', ts('Start Date'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addDateTime('end_date', ts('End Date'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addSelect('status_id', array(), TRUE);

    if (!$this->_isTemplate) {
      $result = civicrm_api3('HRVacancy', 'get', array('is_template' => 1, 'return' => 'position'));
      foreach ($result['values'] as $id => $vacancy) {
        $templates[$id] = $vacancy['position'];
      }
      $this->add('select', 'template_id', ts('From Template'), array('' => ts('- select -')) + $templates, FALSE, array('class' => 'crm-select2 huge'));

      $CaseStatuses = CRM_Core_OptionGroup::values('case_status', FALSE, FALSE, FALSE, " AND grouping = 'Vacancy'");
      $include = &$this->addElement('advmultiselect', 'stages',
        '', $CaseStatuses,
        array(
          'size' => 5,
          'style' => 'width:150px',
          'class' => 'advmultiselect',
        )
      );
      $include->setButtonAttributes('add', array('value' => ts('Enable >>')));
      $include->setButtonAttributes('remove', array('value' => ts('<< Disable')));
    }

    $entities = array(
      'Individual' =>
      array(
        array(
          'entity_name' => 'contact_1',
          'entity_type' => 'IndividualModel',
        ),
      ),
        'Activity' => array(
          array(
            'entity_name' => 'activity_1',
            'entity_type' => 'ActivityModel',
        ),
      ),
    );
    $this->addProfileSelector('application_profile', '', array('Individual', 'Contact'), array(), $entities['Individual']);
    $this->addProfileSelector('evaluation_profile', '', array('Activity'), array(), $entities['Activity']);

    $permissions = CRM_Core_Permission_Base::getAllModulePermissions();
    foreach (array('view Applicants', 'manage Applicants', 'evaluate Applicants', 'administer Applicants') as $permission) {
      $vacancyPermissions[$permission] = array_pop(explode(':', $permissions[$permission]));
    }

    $rowCount = 5;
    for ($rowNumber = 1; $rowNumber <= $rowCount; $rowNumber++) {
      $this->add(
        'select', "permission[{$rowNumber}]",
        '', array('' => ts('- select -')) + $vacancyPermissions,
        FALSE, array('class' => 'crm-select2 huge')
      );
      $this->addEntityRef("permission_contact_id[{$rowNumber}]");
    }
    $this->assign('rowCount', $rowCount);
    $this->assign('showPermissionRow', 1);

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Function to process the form
   *
   * @access public
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();
    $vacancyParams = CRM_HRRecruitment_BAO_HRVacancy::formatParams($params);

    if ($this->_id) {
      $params['id'] = $this->_id;

      //on Edit first of all delete all the entries from hrvacancy stage and permission if any
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_hrvacancy_stage WHERE vacancy_id = {$this->_id}");
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_hrvacancy_permission WHERE vacancy_id = {$this->_id}");
    }
    else {
      $vacancyParams['created_date'] = date('YmdHis');
      $vacancyParams['created_id'] = CRM_Core_Session::singleton()->get('userID');
    }

    if($this->_isTemplate) {
      $vacancyParams['is_template'] = $this->_isTemplate;
    }

    $result = civicrm_api3('HRVacancy', 'create', $vacancyParams);

    if (isset($params['stages']) && count($params['stages'])) {
      foreach ($params['stages'] as $key => $stage_id) {
        $dao = new CRM_HRRecruitment_DAO_HRVacancyStage();
        $dao->case_status_id = $stage_id;
        $dao->vacancy_id = $result['id'];
        $dao->weight = $key+1;
        $dao->save();
      }
    }

    foreach (array('application_profile', 'evaluation_profile') as $profileName) {
      if (!empty($params[$profileName])) {
        $dao = new CRM_Core_DAO_UFJoin();
        $dao->module = 'Vacancy';
        $dao->entity_table = 'civicrm_hrvacancy';
        $dao->entity_id = $result['id'];
        $dao->module_data = $profileName;

        if ($this->_id) {
          $dao->find(TRUE);
        }
        $dao->uf_group_id = $params[$profileName];
        $dao->save();
      }
    }

    if (!empty($params['permission']) && !empty($params['permission_contact_id'])) {
      foreach ($params['permission'] as $key => $permission) {
        if ($permission && $params['permission_contact_id'][$key]) {
          $dao = new CRM_HRRecruitment_DAO_HRVacancyPermission();
          $dao->contact_id = $params['permission_contact_id'][$key];
          $dao->permission = $permission;
          $dao->vacancy_id = $result['id'];
          $dao->save();
        }
      }
    }
   
    if ($this->_action & CRM_Core_Action::UPDATE) {
      if ($this->controller->getButtonName('submit') == "_qf_HRVacancy_next") {
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/vacancy/find', 'reset=1'));
      }
    } 
  }
}
