<?php
/*
 +--------------------------------------------------------------------+
 | CiviHR version 1.2                                                 |
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
 * This file is for civiHR Absence
 */
class CRM_HRAbsence_Form_AbsenceRequest extends CRM_Core_Form {
  public $_customValueCount;
  public $_activityId;
  public $_activityTypeID;
  public $_loginUserID;
  public $_targetContactID;
  public $_managerContactID;
  public $count;
  public $_actStatusId;
  public $_mode;
  protected $_aid;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    $this->_aid = CRM_Utils_Request::retrieve('aid', 'Int', $this);
    $session = CRM_Core_Session::singleton();
    $this->_loginUserID = $session->get('userID');
    if (CRM_Utils_Request::retrieve('cid', 'Positive', $this)) {
      $this->assign('contactId', CRM_Utils_Request::retrieve('cid', 'Positive', $this));
    }
    $activityTypes = CRM_Core_PseudoConstant::activityType();
    $resultHoliday = civicrm_api3('Activity', 'get', array(
      'activity_type_id' => array_search('Public Holiday', $activityTypes),
    ));
    $publicHolidays = array();
    foreach ($resultHoliday['values'] as $key => $val) {
      $pubDate = date("M j, Y", strtotime($val['activity_date_time']));
      $publicHolidays[$pubDate] = $val['subject'];
    }
    $publicHolidays = json_encode($publicHolidays);
    $this->assign('publicHolidays', $publicHolidays);

    if (($this->_action == CRM_Core_Action::VIEW || $this->_action == CRM_Core_Action::UPDATE)) {
      $this->_activityId = CRM_Utils_Request::retrieve('aid', 'String', $this);

      $this->assign('upActivityId', $this->_activityId);
      $resultAct = civicrm_api3('Activity', 'get', array(
        'sequential' => 1,
        'id' => $this->_activityId,
        'return.target_contact_id' => 1,
        'return.assignee_contact_id' => 1,
        'return.source_contact_id' => 1,
        'option.limit' => 31,
      ));
      $this->_activityTypeID = $resultAct['values'][0]['activity_type_id'];
      $this->_targetContactID = $resultAct['values'][0]['target_contact_id'][0];
      $this->_actStatusId = $resultAct['values'][0]['status_id'];

      //condition to check if it has any manager against this absence
      if (array_key_exists(0, $resultAct['values'][0]['assignee_contact_id'])) {
        $this->_managerContactID = $resultAct['values'][0]['assignee_contact_id'][0];
      }

      //Mode is edit if user has edit or admisniter permission or is manager to this absence or
      //(target/requested user and action is update and has manage own Absence permission)
      //(else mode is view if the action is view or already reviewed) and has (view permission
      //or (manage own absence permission and logged in user is target contact itself)
      $absenceStatuses = CRM_HRAbsence_BAO_HRAbsenceType::getActivityStatus();
      if (CRM_Core_Permission::check('administer CiviCRM') ||
        CRM_Core_Permission::check('edit HRAbsences') ||
        $this->_loginUserID == $this->_managerContactID || (
          $absenceStatuses[$this->_actStatusId] == 'Requested' &&
          $this->_action == CRM_Core_Action::UPDATE &&
          $this->_targetContactID == $this->_loginUserID &&
          CRM_Core_Permission::check('manage own HRAbsences')
        )
      ) {
        $this->_mode = 'edit';
      }
      elseif (($this->_action == CRM_Core_Action::VIEW ||
          $absenceStatuses[$this->_actStatusId] != 'Requested') && (
            CRM_Core_Permission::check('view HRAbsences') || (
              CRM_Core_Permission::check('manage own HRAbsences') &&
              $this->_targetContactID = $this->_loginUserID
            )
          )
      ) {
       $this->_mode = 'view';
      }

      //check for ACL View/Edit permission
      if (empty($this->_mode)) {
        if (self::isContactAccessible($this->_targetContactID) == CRM_Core_Permission::EDIT) {
          $this->_mode = 'edit';
        }
        elseif (self::isContactAccessible($this->_targetContactID) == CRM_Core_Permission::VIEW) {
          $this->_mode = 'view';
        }
      }

      $displayName = CRM_Contact_BAO_Contact::displayName($this->_targetContactID);
      $activityTypes = CRM_HRAbsence_BAO_HRAbsenceType::getActivityTypes();
      $activityType = $activityTypes[$this->_activityTypeID];
      $activity = CRM_HRAbsence_BAO_HRAbsenceType::getActivityStatus();
      $activityStatus = $activity[$this->_actStatusId];
      CRM_Utils_System::setTitle(ts("Absence for  %1 (%2, %3)", array(1 => $displayName, 2 => $activityType, 3 => $activityStatus) ));

      if ($this->_action == CRM_Core_Action::VIEW) {
        $groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity', $this, $this->_activityId, 0, $this->_activityTypeID);
        CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);
      }
      else {
        $this->assign('activityType', $this->_activityTypeID);
        CRM_Custom_Form_CustomData::preProcess(
          $this, NULL, $this->_activityTypeID,
          1, 'Activity', $this->_activityId, TRUE
        );
        $this->assign('customValueCount', $this->_customValueCount);
      }
    }
    elseif ($this->_action == CRM_Core_Action::ADD) {
      $this->_mode = 'edit';
      CRM_Utils_System::setTitle(ts('Absence Request: Add'));
      $this->_activityTypeID = CRM_Utils_Request::retrieve('atype', 'Positive', $this);

      if ($this->_activityTypeID) {
        //only custom data has preprocess hence directly call it
        $this->assign('activityType', $this->_activityTypeID);
        CRM_Custom_Form_CustomData::preProcess(
          $this, NULL, $this->_activityTypeID,
          1, 'Activity', NULL, TRUE
        );
        $this->assign('customValueCount', $this->_customValueCount);
      }

      if (CRM_Utils_Request::retrieve('cid', 'Positive', $this)) {
        $this->_targetContactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      }
      else {
        //if there is no cid passed then consider target contact as logged in user
        //who will applying leave for himself
        $this->_targetContactID = $this->_loginUserID;
      }
      $this->_managerContactID = CRM_Core_DAO::getFieldValue('CRM_HRJob_DAO_HRJob', $this->_targetContactID, 'manager_contact_id', 'contact_id');
    }

    $this->assign('mode', $this->_mode);
    CRM_Core_Resources::singleton()->addStyleFile('org.civicrm.hrabsence', 'css/hrabsence.css');
    parent::preProcess();
  }

  public function setDefaultValues() {
    if ($this->_activityId && $this->_action != CRM_Core_Action::VIEW) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  function buildQuickForm() {
    if (!$this->_mode) {
      $action = array(
        CRM_Core_Action::VIEW => 'view',
        CRM_Core_Action::UPDATE => 'edit',
      );
      CRM_Core_Error::fatal(ts('You do not have permission to %1 this absence', array('%1' => $action[$this->_action])));
      return;
    }

    $statusTypes = array_flip(CRM_HRAbsence_BAO_HRAbsenceType::getActivityStatus('name'));
    $buttons = array(
      'cancel' => array(
        'type' => 'submit',
        'name' => ts('Cancel'),
        'subName' => 'cancelbutton'
      ),
      $statusTypes['Scheduled'] => array(
        'type' => 'submit',
        'name' => ts('Save'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ),
      $statusTypes['Completed'] => array(
        'type' => 'submit',
        'name' => ts('Approve'),
        'subName' => 'approve'
      ),
      $statusTypes['Cancelled'] => array(
        'type' => 'submit',
        'name' => ts('Cancel Absence Request'),
        'subName' => 'cancel'
       ),
      $statusTypes['Rejected'] => array(
        'type' => 'submit',
        'name' => ts('Reject'),
        'subName' => 'reject'
        ),
      );

    if ($this->_mode == 'edit') {
      $this->assign('permEditContact', 1);
    }
    $conId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (isset($conId) && $conId == 0) {
      $name = "contacts";
      $this->add('text', $name, "contacts");
      $this->add('hidden', $name . '_id');
      $contactDataURL = CRM_Utils_System::url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=contact&contact_type=individual', FALSE, NULL, FALSE);
      $this->assign('contactDataURL', $contactDataURL);
    }

    if ($this->_mode == 'edit' || $this->_mode == 'view') {
      if (self::isContactAccessible($this->_targetContactID)) {
        $this->assign('permContact', 1);
        $this->assign('emp_id', $this->_targetContactID);
      }
    }

    $activityTypes = CRM_HRAbsence_BAO_HRAbsenceType::getActivityTypes();

    $this->assign('absenceType', $activityTypes[$this->_activityTypeID]);
    $resultHRJob = civicrm_api3('HRJob', 'get', array(
      'sequential' => 1,
      'contact_id' => $this->_targetContactID,
      'is_primary' => 1,
    ));
    if (!empty($resultHRJob['values'])) {
      $this->assign('emp_position', $resultHRJob['values'][0]['position']);
    }
    $this->assign('emp_name', CRM_Contact_BAO_Contact::displayName($this->_targetContactID));

    if ($this->_mode == 'view') {
      $resultAbsences = civicrm_api3('Activity', 'get', array(
        'source_record_id' => $this->_activityId,
        'option_sort' => "activity_date_time ASC",
        'option.limit' => 31,
      ));
      $countDays = 0;
      $absenceDateDuration = array();
      foreach ($resultAbsences['values'] as $key => $val) {
        $convertedDate = date("M d, Y (D)", strtotime($val['activity_date_time']));
        if ($val['duration'] == "480") {
          $converteddays = "Full Day";
          $countDays = $countDays + 1;
        }
        elseif ($val['duration'] == "240") {
          $converteddays = "Half Day";
          $countDays = $countDays + 0.5;
        }
        else {
          $converteddays = "Holiday";
        }
        $absenceDateDuration[$convertedDate] = $converteddays;
      }
      $keys = array_keys($absenceDateDuration);
      $count = count($keys) - 1;
      $fromdateVal = explode('(', $keys[0]);
      $todateVal = explode('(', $keys[$count]);
      $this->assign('fromDate', date("M j, Y", strtotime($fromdateVal[0])));
      $this->assign('toDate', date("M j, Y", strtotime($todateVal[0])));
      $this->assign('absenceDateDuration', $absenceDateDuration);
      $this->_fromDate = $fromdateVal[0];
      $this->_toDate = $todateVal[0];
      $this->assign('totalDays', $countDays);
    }

    if (($this->_action && (CRM_Core_Action::ADD || CRM_Core_Action::UPDATE)) && $this->_mode == 'edit') {
      $this->assign('customDataSubType', $this->_activityTypeID);
      if ($this->_customValueCount) {
        CRM_Custom_Form_CustomData::buildQuickForm($this);
      }

      $this->assign('loginUserID', $this->_loginUserID);
      if (empty($this->_managerContactID)) {
        $this->_managerContactID = NULL;
      }
      $this->add('hidden', 'date_values', '', array('id' => 'date_values'));
    }
    $this->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'activityDate'));
    $this->addDate('end_date', ts('End Date / Time'), FALSE, array('formatType' => 'activityDate'));

    if ($this->_mode == 'edit') {
      if ($this->_action && ($this->_action == CRM_Core_Action::ADD)) {
        $this->addButtons(
          array(
            array(
              'type' => 'submit',
              'name' => ts('Save'),
              'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
              'isDefault' => TRUE,
            ),
          )
        );
      }
      else {
        $this->add('hidden', 'source_record_id', $this->_aid);
        $result = civicrm_api3('Activity', 'get', array(
          'sequential' => 1,
          'source_record_id' => $this->_aid,
          'option_sort' => "activity_date_time ASC",
          'option.limit' => 31,
        ));
        $start_date = date_create($result['values'][0]['activity_date_time']);
        $end_date = date_create($result['values'][$result['count'] - 1]['activity_date_time']);
        $this->assign('fromDate', date_format($start_date, 'm/d/Y'));
        $this->assign('toDate', date_format($end_date, 'm/d/Y'));

        global $user;
        $today = time();
        $date1 = new DateTime(date("M j, Y", $today));
        $intervals = $date1->diff($end_date);

        if (CRM_Core_Permission::check('administer CiviCRM') ||
          CRM_Core_Permission::check('edit HRAbsences') ||
          ((($intervals->days >= 0) && ($intervals->invert == 0)) &&
            (($this->_managerContactID && $this->_managerContactID == $this->_loginUserID) ||
              self::isContactAccessible($this->_targetContactID) == CRM_Core_Permission::EDIT)
          )
        ) {
          unset($buttons[$this->_actStatusId]);
        }
        elseif ((CRM_Core_Permission::check('manage own HRAbsences') && $this->_targetContactID == $this->_loginUserID)) {
          unset($buttons[$statusTypes['Completed']], $buttons[$statusTypes['Rejected']]);
        }
        $this->addButtons($buttons);
      }
    }
    elseif ($this->_mode == 'view') {
      global $user;
      $now = time();
      $datetime1 = new DateTime(date("M j, Y", $now));
      $datetime2 = new DateTime($this->_toDate);
      $interval = $datetime1->diff($datetime2);
      $this->addButtons(array($buttons['cancel']));

      if ((($interval->days >= 0) && ($interval->invert == 0)) &&
        ($this->_actStatusId == $statusTypes['Scheduled'] || $this->_actStatusId == $statusTypes['Completed']) &&
        $this->_targetContactID == $this->_loginUserID
      ) {
        $this->addButtons(array($buttons['cancel'], $buttons[$statusTypes['Cancelled']]));
      }
    }
    if ( $this->_action == CRM_Core_Action::UPDATE || $this->_action == CRM_Core_Action::ADD ) {
      $this->addFormRule(array('CRM_HRAbsence_Form_AbsenceRequest', 'formRule'));
    }
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    if (isset($fields['start_date_display'])) {
      $dateFrom = $fields['start_date_display'];
    }
    if (isset($fields['start_date_display'])) {
      $dateTo = $fields['end_date_display'];
    }
    if (isset($dateFrom) && isset($dateTo)){
      $days = (strtotime($dateTo)- strtotime($dateFrom))/24/3600;
      $days = $days + 1;
    }
    if (empty($dateFrom)) {
      $errors['start_date'] = ts('From date is required.');
    }
    if (empty($dateTo)) {
      $errors['end_date'] = ts('End date is required.');
    }
    if (strtotime(isset($fields['start_date_display'])) && strtotime(isset($fields['end_date_display'])) && strtotime(isset($fields['start_date_display'])) > strtotime(isset($fields['end_date_display']))) {
      $errors['end_date'] = ts('From date cannot be greater than to date.');
    }
    if (isset($days) && $days > 31) {
      $errors['end_date'] = ts('End date should be within a month.');
    }
    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $submitValues = $this->_submitValues;
    if (!empty($submitValues['contacts_id'])) {
      $this->_targetContactID = $submitValues['contacts_id'];
    }
    $absentDateDurations = array();

    if (!empty($submitValues['date_values'])) {
      foreach (explode('|', $submitValues['date_values']) as $key => $dateString) {
        if ($dateString) {
          $values = explode('(', $dateString);
          $date = CRM_Utils_Date::processDate($values[0]);
          $valuesDate = explode(':', $dateString);
          $absentDateDurations[$date] = (int) $valuesDate[1];
        }
      }
    }

    $activityStatus = CRM_HRAbsence_BAO_HRAbsenceType::getActivityStatus('name');
    if ($this->_action & (CRM_Core_Action::ADD)) {
      $activityParam = array(
        'sequential' => 1,
        'source_contact_id' => $this->_loginUserID,
        'target_contact_id' => $this->_targetContactID,
        'assignee_contact_id' => $this->_managerContactID,
        'activity_type_id' => $this->_activityTypeID,
      );

      //we want to keep the activity status in Scheduled for new absence
      $activityParam['status_id'] = CRM_Utils_Array::key('Scheduled', $activityStatus);
      $result = civicrm_api3('Activity', 'create', $activityParam);

      //save the custom data
      if (!empty($submitValues['hidden_custom'])) {
        $customFields = CRM_Utils_Array::crmArrayMerge(
          CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE, $this->_activityTypeID),
          CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE, NULL, NULL, TRUE)
        );
        $customValues = CRM_Core_BAO_CustomField::postProcess($submitValues, $customFields, $result['id'], 'Activity');
        CRM_Core_BAO_CustomValueTable::store($customValues, 'civicrm_activity', $result['id']);
      }

      $activityLeavesParam = array(
        'sequential' => 1,
        'source_record_id' => $result['id'],
        'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type', 'Absence', 'name'),
      );
      $activityLeavesParam['status_id'] = $activityParam['status_id'];
      foreach ($absentDateDurations as $date => $duration) {
        $activityLeavesParam['activity_date_time'] = $date;
        $activityLeavesParam['duration'] = $duration;
        civicrm_api3('Activity', 'create', $activityLeavesParam);
      }

      CRM_Core_Session::setStatus(ts('Absence(s) have been applied.'), ts('Saved'), 'success');
      $buttonName = $this->controller->getButtonName();
      if ($buttonName == $this->getButtonName('submit')) {
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/absence/set', "reset=1&action=view&aid={$result['id']}"));
      }
    }
    elseif ($this->_mode == 'edit') {
      if (array_key_exists('_qf_AbsenceRequest_submit_cancel', $submitValues)) {
        $statusId = CRM_Utils_Array::key('Cancelled', $activityStatus);
        $activityParam = array(
          'sequential' => 1,
          'id' => $this->_activityId,
          'activity_type_id' => $this->_activityTypeID,
          'status_id' => $statusId
        );
        $result = civicrm_api3('Activity', 'create', $activityParam);
        CRM_Core_Session::setStatus(ts('Absence(s) have been Cancelled.'), ts('Cancelled'), 'success');
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/absence/set', "reset=1&action=view&aid={$result['id']}"));
      }
      elseif (array_key_exists('_qf_AbsenceRequest_submit_approve', $submitValues)) {
        $statusId = CRM_Utils_Array::key('Completed', $activityStatus);
        $activityParam = array(
          'sequential' => 1,
          'id' => $this->_activityId,
          'activity_type_id' => $this->_activityTypeID,
          'status_id' => $statusId
        );
        $result = civicrm_api3('Activity', 'create', $activityParam);
        CRM_Core_Session::setStatus(ts('Absence(s) have been Approved.'), ts('Approved'), 'success');
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/absence/set', "reset=1&action=view&aid={$result['id']}"));
      }
      elseif (array_key_exists('_qf_AbsenceRequest_submit_reject', $submitValues)) {
        $statusId = CRM_Utils_Array::key('Rejected', $activityStatus);
        $activityParam = array(
          'id' => $this->_activityId,
          'activity_type_id' => $this->_activityTypeID,
          'status_id' => $statusId
        );
        $result = civicrm_api3('Activity', 'create', $activityParam);
        CRM_Core_Session::setStatus(ts('Absence(s) have been Rejected.'), ts('Rejected'), 'success');
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/absence/set', "reset=1&action=view&aid={$result['id']}"));
      }
      elseif (array_key_exists('_qf_AbsenceRequest_submit_cancelbutton', $submitValues)) {
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_targetContactID}#hrabsence/list"));
      }
      else {
        $result = civicrm_api3('Activity', 'get', array(
          'source_record_id' => $submitValues['source_record_id'],
          'option.limit' => 31,
        ));
        foreach ($result['values'] as $row_result) {
          civicrm_api3('Activity', 'delete', array(
            'id' => $row_result['id'],
          ));
        }
        foreach ($absentDateDurations as $date => $duration) {
          $result = civicrm_api3('Activity', 'create', array(
            'activity_type_id' => $this->_activityTypeID,
            'source_record_id' => $submitValues['source_record_id'],
            'activity_date_time' => $date,
            'duration' => $duration,
          ));
        }
        $buttonName = $this->controller->getButtonName();
        if ($buttonName == $this->getButtonName('submit')) {
          $this->_aid = $submitValues['source_record_id'];
          return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/absence/set', "reset=1&action=view&aid={$submitValues['source_record_id']}"));
        }
      }
    }
    else {
      if (CRM_Utils_Request::retrieve('aid', 'Positive', $this)) {
        $activityIDs = CRM_Utils_Request::retrieve('aid', 'Positive', $this);
      }

      if (array_key_exists('_qf_AbsenceRequest_submit_cancel', $submitValues)) {
        $statusId = CRM_Utils_Array::key('Cancelled', $activityStatus);
        $statusMsg = ts('Absence(s) have been Cancelled');
      }
      elseif (array_key_exists('_qf_AbsenceRequest_submit_cancelbutton', $submitValues)) {
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_targetContactID}#hrabsence/list"));
      }
      civicrm_api3('Activity', 'create', array(
        'id' => $this->_activityId,
        'activity_type_id' => $this->_activityTypeID,
        'status_id' => $statusId
      ));
      CRM_Core_Session::setStatus($statusMsg, 'success');
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/absence/set', "reset=1&action=view&aid={$activityIDs}"));
    }
  }

  public static function isContactAccessible($contactID) {
    if (CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::EDIT)) {
      return CRM_Core_Permission::EDIT;
    }
    elseif (CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::VIEW)) {
      return CRM_Core_Permission::VIEW;
    }
    else {
      return FALSE;
    }
  }
}