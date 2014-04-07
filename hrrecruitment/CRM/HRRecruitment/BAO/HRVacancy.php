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

class CRM_HRRecruitment_BAO_HRVacancy extends CRM_HRRecruitment_DAO_HRVacancy{

  /**
   * Function to format the Vacancy parameters before saving
   *
   * @return array   Formated array before being used for create/update Vacancy
   */
  public static function formatParams($params) {
    $formattedParams = array();
    $instance = new self();
    $fields = $instance->fields();
    foreach ($fields as $name => $dontCare) {
      if (strpos($name, '_date') !== FALSE && strpos($name, 'created_') === FALSE) {
        $formattedParams[$name]  = CRM_Utils_Date::processDate($params[$name], $params[$name . '_time']);
      }
      elseif ($name == 'is_template' && !array_key_exists('template_id', $params)) {
        $formattedParams[$name] = 1;
      }
      elseif(isset($params[$name])) {
        $formattedParams[$name] = $params[$name];
      }
    }
    return $formattedParams;
  }

  /**
   * This function is to make a copy of a Vacancy
   *
   * @param int     $id          the vacancy id to copy
   *        obj     $newVacancy    object of CRM_HRRecruitment_DAO_HRVacancy
   *        boolean $afterCreate call to copy after the create function
   * @return void
   * @access public
   */
  static function copy($id, $newVacancy = NULL, $afterCreate = FALSE) {
    $vacancyValues = array();
    $vacancyParams = array('id' => $id);
    $returnProperties = array('position', 'salary' , 'status_id', 'is_template');
    CRM_Core_DAO::commonRetrieve('CRM_HRRecruitment_DAO_HRVacancy', $vacancyParams, $vacancyValues, $returnProperties);
    $fieldsFix = ($afterCreate) ? array( ) : array('prefix' => array('position' => ts('Copy of') . ' '));
    if ($newVacancy && is_a($newVacancy, 'CRM_HRRecruitment_DAO_HRVacancy')) {
      $copyVacancy = $newVacancy;
    }

    if (!isset($copyVacancy)) {
      $copyVacancy = &CRM_Core_DAO::copyGeneric('CRM_HRRecruitment_DAO_HRVacancy',
        array('id' => $id),'',
          $fieldsFix
        );
    }
    CRM_Utils_System::flushCache();
    return $copyVacancy;
  }

  static function getVacanciesByStatus() {
    $result = civicrm_api3('HRVacancy', 'get', array('is_template' => 0));
    $statuses = CRM_Core_OptionGroup::values('vacancy_status');
    $vacancies = $statusesCount = array();
    //initialize $statusesCount which hold the number of vacancies of status 'Draft' and 'Open'
    foreach (array('Draft', 'Open') as $statusName) {
      $value = array_search($statusName, $statuses);
      $statusesCount[$value] = 0;
    }

    foreach ($result['values'] as $id => $vacancy) {
      $isDraft = FALSE;
      if (isset($statusesCount[$vacancy['status_id']])) {
        $statusesCount[$vacancy['status_id']] += 1;
        if ($vacancy['status_id'] == array_search('Draft', $statuses)) {
          $isDraft = TRUE;
        }
        $vacancyEntry[$vacancy['status_id']]['vacancies'][$id] = array(
          'position' => $vacancy['position'],
          'location' => $vacancy['location'],
          'date' => CRM_Utils_Date::customFormat($vacancy['start_date'], '%b %E, %Y') . ' - ' . CRM_Utils_Date::customFormat($vacancy['end_date'],'%b %E, %Y'),
        );

        //assign stages by weight
        $stages = CRM_HRRecruitment_BAO_HRVacancyStage::caseStage($id);
        foreach($stages as $stage) {
          $vacancyEntry[$vacancy['status_id']]['vacancies'][$id]['stages'][$stage['weight']] = array(
            'title' => $stage['title'],
            'count' => $stage['count'],
          );
        }
        ksort($vacancyEntry[$vacancy['status_id']]['vacancies'][$id]['stages']);

        $vacancies[$vacancy['status_id']] = array('title' => $statuses[$vacancy['status_id']]) + $vacancyEntry;
      }
    }

    //append $statusCount result to vacancy's position as title
    foreach ($statusesCount as $status => $count) {
      if ($count) {
        $vacancies[$status]['title'] .= " ({$count})";
      }
      else {
        $vacancies += array($status => array('title' => "{$statuses[$status]} ({$count})"));
      }
    }
    return $vacancies;
  }

  static function recentApplicationActivities($limit = 10) {
    $recentActivities = array();

    $customTableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', 'application_case', 'table_name', 'name');

    //Retrieve top $limit(as total activity count) recent activities
    $query = CRM_Case_BAO_Case::getCaseActivityQuery($type = 'any', NULL, $condition = "AND cov_type.name = 'Application' AND t_act.desired_date <= CURRENT_TIMESTAMP ");
    $query .= "LIMIT 0, {$limit}";
    $query = str_replace('ORDER BY case_activity_date ASC', 'ORDER BY case_activity_date DESC', $query);
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $query = "SELECT vacancy_id FROM {$customTableName} WHERE entity_id = {$dao->case_id}";
      $ctDAO = CRM_Core_DAO::executeQuery($query);
      $ctDAO->fetch();

      $vacancyDAO = new self();
      $vacancyDAO->id = $ctDAO->vacancy_id;
      $vacancyDAO->find(TRUE);

      //Take the date diff from Case Activity date to current date and express in Day/Hour/Minute
      $dateDiff = date_diff(date_create(), date_create($dao->case_activity_date));
      $dateString = NULL;
      if ($dateDiff->d) {
        $dateString .= " {$dateDiff->d} day(s)";
      }
      if ($dateDiff->h) {
        $dateString .= " {$dateDiff->h} hour(s)";
      }
      if ($dateDiff->i) {
        $dateString .= " {$dateDiff->i} minute(s)";
      }
      $dateString .= ' ago';

      //Applicant contact link
      $applicant = "<a href='" . CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$dao->contact_id}") . "'>{$dao->sort_name}</a>";

      //Position link
      $position = "<a href='" . CRM_Utils_System::url('civicrm/case/pipeline', "reset=1&vid={$vacancyDAO->id}") . "'>{$vacancyDAO->position}</a>";

      //Case Activity Source link
      $sourceID = civicrm_api3('OptionValue', 'getvalue', array('option_group_id' => 'activity_contacts', 'name' => 'Activity Source', 'return' => 'value'));
      $sourceContact = CRM_Activity_BAO_ActivityContact::getNames($dao->case_activity_id, $sourceID);
      $sourceContactID = key($sourceContact);
      $source = "<a href='" . CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$sourceContactID}") . "'>{$sourceContact[$sourceContactID]}</a>";
      switch ($dao->case_activity_type_name) {
        case 'Open Case':
          $recentActivities[] = array(
            'activity' => "{$applicant} applied for {$position}",
            'time' => $dateString
          );
          break;
        case 'Comment':
          $recentActivities[] = array(
            'activity' => "{$source} commented on {$position}",
            'time' => $dateString
          );
          break;
        case 'Phone Call':
        case 'Meeting':
        case 'Follow up':
          $recentActivities[] = array(
            'activity' => "{$source} had a {$dao->case_activity_type_name} with {$applicant} (vis-a-vis {$position})",
            'time' => $dateString
          );
          break;
        case 'Email':
          $recentActivities[] = array(
            'activity' => "{$source} sent email to {$applicant}",
            'time' => $dateString
          );
          break;
        case 'Change Case Status':
          $recentActivities[] = array(
            'activity' => "{$source} changed the status of {$position}",
            'time' => $dateString
          );
          break;
      }
    }

    return $recentActivities;
  }
}
