{*
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
*}
<div class="crm-clearfix hr-pipeline-tab">
  <div class="hr-dashboard-vacancy-summary">
    {include file="CRM/HRRecruitment/Page/Summary.tpl"}
  </div>
  <div class="hr-dashboard-recent-activity">
    <div class="hr-recent-activity-title">
      <h1>{ts}Recent Activities{/ts}</h1>
    </div>
    <ul>
      {foreach from=$recentActivities item="status" item="activity"}
        <li>
          <div class="hr-recent-activity-block">
            {$activity.activity}
          </div>
          <div>
            {$activity.time}
          </div>
        </li>
      {/foreach}
    </ul>
  </div>
</div>
