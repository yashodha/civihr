<script id="hrjob-general-template" type="text/template">
<form>
  <h3>
    {ts}General{/ts}{literal} <%- (isNewDuplicate) ? '(' + ts('New Copy of "%1"', {1: position}) + ')' : '' %>{/literal} 
    {literal}<% if (!isNew) { %> {/literal}
    <a class='css_right hrjob-revision-link' data-table-name='civicrm_hrjob' href='#' title='{ts}View Revisions{/ts}'>(View Revisions)</a>
    {literal}<% } %>{/literal}
  </h3>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-position">{ts}Position{/ts}</label>
    </div>
    <div class="crm-content">
      <input id="hrjob-position" name="position" class="crm-form-text" type="text" />
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-title">{ts}Title{/ts}</label>
    </div>
    <div class="crm-content">
      <input id="hrjob-title" name="title" class="crm-form-text" type="text" />
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-contract_type">{ts}Contract Type{/ts}</label>
    </div>
    <div class="crm-content">
    {literal}
      <%= RenderUtil.select({
        id: 'hrjob-contract_type',
        name: 'contract_type',
        entity: 'HRJob'
      }) %>
    {/literal}
    {include file="CRM/HRJob/Page/EditOptions.tpl" group='hrjob_contract_type'}
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-department">{ts}Department{/ts}</label>
    </div>
    <div class="crm-content">
      {literal}
        <%= RenderUtil.select({
        id: 'hrjob-department',
        name: 'department',
        entity: 'HRJob'
        }) %>
      {/literal}
      {include file="CRM/HRJob/Page/EditOptions.tpl" group='hrjob_department'}
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-level_type">{ts}Level{/ts}</label>
    </div>
    <div class="crm-content">
    {literal}
      <%= RenderUtil.select({
        id: 'hrjob-level_type',
        name: 'level_type',
        entity: 'HRJob'
      }) %>
    {/literal}
    {include file="CRM/HRJob/Page/EditOptions.tpl" group='hrjob_level_type'}
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-manager_contact_id">{ts}Manager{/ts}</label>
    </div>
    <div class="crm-content">
      <input id="hrjob-manager_contact_id" name="manager_contact_id" class="crm-form-entityref" data-api-params='{literal}{"params":{"contact_type":"Individual"}}{/literal}' placeholder="{ts}- select -{/ts}" />
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-level_type">{ts}Normal Place of Work{/ts}</label>
    </div>
    <div class="crm-content">
    {literal}
      <%= RenderUtil.select({
      id: 'hrjob-level_type',
      name: 'location',
      entity: 'HRJob'
      }) %>
    {/literal}
    {include file="CRM/HRJob/Page/EditOptions.tpl" group='hrjob_location'}
    </div>
  </div>

  <div class="crm-summary-row hrjob-is_primary-row">
    <div class="crm-label">
      <label for="hrjob-is_primary">{ts}Is Primary{/ts}</label>
    </div>
    <div class="crm-content">
      <input id="hrjob-is_primary" name="is_primary" type="checkbox" />
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-period_type">{ts}Contract Duration{/ts}</label>
    </div>
    <div class="crm-content">
    {literal}
      <%= RenderUtil.select({
        id: 'hrjob-period_type',
        name: 'period_type',
        entity: 'HRJob'
      }) %>
    {/literal}
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-period_start_date">{ts}Start Date{/ts}</label>
    </div>
    <div class="crm-content">
      <input id="hrjob-period_start_date" name="period_start_date" type="text" />
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-period_end_date">{ts}End Date{/ts}</label>
    </div>
    <div class="crm-content">
      <input id="hrjob-period_end_date" name="period_end_date" type="text" />
    </div>
  </div>

  <div class="crm-summary-row">
    <div class="crm-label">
      <label for="hrjob-notice_amount">{ts}Notice Period{/ts}</label>
    </div>
    <div class="crm-content">
      <input id="hrjob-notice_amount" name="notice_amount" type="text" />
      {literal}
      <%= RenderUtil.select({
        id: 'hrjob-notice_unit',
        name: 'notice_unit',
        entity: 'HRJob'
      }) %>
      {/literal}
    </div>
  </div>

  {literal}<% if (!isNewDuplicate) { %> {/literal}
  <button class="crm-button standard-save">{ts}Save{/ts}</button>
  {literal}<% } else { %>{/literal}
  <button class="crm-button standard-save">{ts}Save New Copy{/ts}</button>
  {literal}<% } %>{/literal}
  <button class="crm-button standard-reset">{ts}Reset{/ts}</button>
</form>
</script>
