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
 * This file is for civiHR Vacancy Search
 */
class CRM_HRRecruitment_Form_Search extends CRM_Core_Form {

  /**
   * the status id of the vacancy that we are searching
   *
   * @var int
   * @public
   */
  public $_statusID;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {

    $this->_statusID = CRM_Utils_Request::retrieve('status', 'String', $this);
    $vacancyStatus = '';
    if ($this->_statusID) {
      $status = CRM_Core_OptionGroup::values('vacancy_status', FALSE, FALSE,
        FALSE, "AND v.value = {$this->_statusID}");
      $vacancyStatus = $status[$this->_statusID];
    }

    CRM_Utils_System::setTitle(ts('Find %1 Vacancies', array(1 => $vacancyStatus)));
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = array();
    if (!empty($this->_statusID)) {
      $defaults["status_type_id[$this->_statusID]"] = 1;
    }

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->addElement('text', 'job_position', ts('Job Position'));

    $status = CRM_Core_OptionGroup::values('vacancy_status', FALSE);
    foreach ($status as $statusId => $statusName) {
      $this->addElement('checkbox', "status_type_id[$statusId]", 'Status', $statusName);
    }

    $location = CRM_Core_OptionGroup::values('hrjob_location', FALSE);
    foreach ($location as $locationId => $locationName) {
      $this->addElement('checkbox', "location_type_id[$locationId]", 'Location', $locationName);
    }
    $this->addButtons(
      array(
        array(
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * The post processing of the form gets done here.
   *
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   *
   * @param
   *
   * @return void
   * @access public
   */
  function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    $parent->set('searchResult', 1);
    $parent->set('status', null);
    if (!empty($params)) {
      $fields = array('job_position', 'status_type_id', 'location_type_id');
      foreach ($fields as $field) {
        if (isset($params[$field]) && !CRM_Utils_System::isNull($params[$field])) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }
}


