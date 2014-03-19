{*
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
*}
{* Form to Add, Edit, Approve, Reject absence request *}
<div class="crm-block crm-content-block">
  <table class="abempInfo" style="width: auto; border: medium none ! important;">
    <tr>
      <td>{$form.contacts_id.label}</td>
      <td colspan="2">{$form.contacts_id.html}</td>
    </tr>
    <tr id="position">
      <td>{ts}Position{/ts}</td>
      <td colspan="2">{$emp_position}</td>
    </tr>
    <tr>
      <td>{ts}Absence Type{/ts}</td> 
      <td colspan="2">{$absenceType}</td> 
    </tr>
    <tr class="crm-event-manage-eventinfo-form-block-start_date">
      <td class="label">{ts}Dates{/ts}</td>
      <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}</td>
      <td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}</td>
    </tr>
  </table>
  <table id="tblabsence" class="report">
    <tbody>
      <tr class="tblabsencetitle">
        <td>{ts}Date{/ts}</td>
        <td>{ts}Absence{/ts}</td>
      </tr>
    </tbody>
  </table>

   <div id="commentDisplay">
     {ts}(Please mark absences for dates that you need off. If you don't normally work on a weekened or holiday, omit it.){/ts}
   </div>

</div>
<div id='customData'>{include file="CRM/Custom/Form/CustomData.tpl"}</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{include file="CRM/common/customData.tpl" location="bottom"}
{literal}
  <script type="text/javascript">
    cj(function($) {
    var
      $form = $('form#{/literal}{$form.formName}{literal}'),
      additn = 0,
      uID = '{/literal}{$loginUserID}{literal}',
      absencesTypeID = $('#activity_type_id', $form).val(),
      upActivityId = '{/literal}{$upActivityId}{literal}',
      upfromDate = '{/literal}{$fromDate}{literal}',
      uptoDate = '{/literal}{$toDate}{literal}',
      difDate,
      pubHoliday,
      param = {};

    $("input[name=contacts_id]", $form).change(function() {
      CRM.api3('HRJob', 'get', {'sequential': 1, 'contact_id': $(this).val(), 'is_primary': 1})
        .done(function(data) {
          $.each(data.values, function(key, value) {
            $('#position td:nth-child(2)', $form).html(value.position);
          });
        });
    });
    
    $('span.crm-error', $form).insertAfter('input#end_date_display', $form);
      {/literal}
        {if $customValueCount}
          {foreach from=$customValueCount item="groupCount" key="groupValue"}
            {if $groupValue}{literal}
              for ( var i = 1; i < {/literal}{$groupCount}{literal}; i++ ) {
                CRM.buildCustomData( 'Activity', {/literal}"{$activityType}"{literal}, i, {/literal}{$groupValue}{literal}, true);
              }
              {/literal}
            {/if}
          {/foreach}
        {/if}
      {literal}

    $('#start_date_display', $form).change(function() {
      addabsencetbl();
    })
    $('#end_date_display', $form).change(function() {
      addabsencetbl();
    })
  
      {/literal}{if $mode eq 'edit'}{literal}
        $("#tblabsence", $form).show();
        $('input#start_date_display', $form).datepicker('setDate', upfromDate);
        $('input#end_date_display', $form).datepicker('setDate', uptoDate);
        end_date = $('#end_date_display', $form).datepicker( "getDate" );
        start_date = $('#start_date_display', $form).datepicker( "getDate" );
        difDate = Math.floor((end_date - start_date) / 86400000);
        pubHoliday = {/literal}{$publicHolidays}{literal};
        CRM.api('Activity', 'get', {'sequential': 1, 'source_record_id': upActivityId, 'option_sort': 'activity_date_time ASC', 'option.limit': 31},
          {success: function(data) {
            $.each(data.values, function(key, value) {
            var val = value.activity_date_time;
            param[val]=value.duration;
          });   
	  var x=0,
            selectopt,
            totalDays=0;
          $.each(param, function(key, value) {
            var datepicker = key,
              parms = datepicker.split("-"),
              subpar2 = parms[2].substring(0,3),
              joindate = new Date(parms[1]+"/"+subpar2+"/"+parms[0]),
              absenceDate = joindate.toDateString(),
              abdate = absenceDate.substring(4,7)+' '+joindate.getDate()+','+' '+joindate.getFullYear()+' ('+absenceDate.substring(0,3)+')',
              abday = absenceDate.substring(0,3),
              sDate = absenceDate.substring(4,7)+' '+joindate.getDate()+','+' '+joindate.getFullYear(),
              holidayDes,
              holidayDesc,
              abdate,
              createSelectBox;
            if ( sDate in pubHoliday ) {
              holidayDes = pubHoliday[sDate];
              holidayDesc = ", "+holidayDes;
              abdate = absenceDate.substring(4,7)+' '+joindate.getDate()+','+' '+joindate.getFullYear()+' ('+absenceDate.substring(0,3)+''+holidayDesc+')';
            }
            else {
              abdate = absenceDate.substring(4,7)+' '+joindate.getDate()+','+' '+joindate.getFullYear()+' ('+absenceDate.substring(0,3)+')';
            }
	    createSelectBox = '<tr class="trabsence"><td><label id="label_'+x+'" >'+abdate+'</label></td><td><select id="options_'+x+'" class="crm-form-select crm-select2"><option value="1">Full Day</option><option value="0.5">Half Day</option><option value=""></option></select></td></tr>';
            $('form#AbsenceRequest table#tblabsence tbody').append(createSelectBox);
            if (value==240) {
              $("#options_"+x).val('0.5');
              selectopt = $('#options_'+x+' :selected').val();
              totalDays = new Number(totalDays) + new Number(selectopt);
	    } 
	    else if (value==480) {
	      $("#options_"+x, $form).val('1');
	      selectopt = $('#options_'+x+' :selected', $form).val();
	      totalDays = new Number(totalDays) + new Number(selectopt);
	    }
	    else {
	      $("#options_"+x, $form).val('');
                if (abday == 'Sat' || abday == 'Sun') {
                  $("#options_"+x, $form).attr("disabled","disabled");
                }
           }
	    x = new Number(x) + 1;
 	  });
          if (totalDays <= 1) {
	    totalDays += ' {/literal}{ts}day{/ts}{literal}';
          }
          else {
            totalDays += ' {/literal}{ts}days{/ts}{literal}';
	  }
	  $('#countD', $form).html(totalDays);
        }
      });

      $("#_qf_AbsenceRequest_done_save-bottom", $form).click(function(event){
        var dateValues = [],
          end_date = $('#end_date_display', $form).datepicker( "getDate" ),
          start_date = $('#start_date_display', $form).datepicker( "getDate" ),
          diDate = Math.floor((end_date - start_date) / 86400000),
          selDate,
          selectopt=0;
        for (var x = 0; x <= diDate; x++) {
          selDate = $('#label_'+x, $form).text();
          selectopt = $('#options_'+x+' :selected', $form).text();
          if (selectopt == "Full Day"){
            dateValues[x] = selDate +":" + "480";
          }
          else {
            if (selectopt == "Half Day"){
              dateValues[x] = selDate +":" + "240";
            }
	    else {
	      dateValues[x] = selDate +":" + "0";
	    }
          }
        }
    	$("#date_values", $form).val(dateValues.join('|'));
      });

{/literal}{/if}{literal}
{/literal}{if $action eq 1}{literal}
  $("#tblabsence", $form).hide();
  $("#commentDisplay", $form).hide();
  $("#_qf_AbsenceRequest_done_save-bottom", $form).click(function(event){
    save();
  });
  $("#_qf_AbsenceRequest_done_saveandapprove-bottom", $form).click(function(event){
    save();
  });
  function save () {
    var dateValues = [];
    var end_date = $('#end_date_display', $form).datepicker( "getDate" ),
    start_date = $('#start_date_display', $form).datepicker( "getDate" ),
    diDate = Math.floor((end_date - start_date) / 86400000),
    selDate,
    selectopt = 0;
    for (var x = 0; x <= diDate; x++) {
      selDate = $('#label_'+x, $form).text();
      selectopt = $('#options_'+x+' :selected', $form).text();
      if (selectopt == "Full Day") {
        dateValues[x] = selDate +":" + "480";
      }
      else {
        if (selectopt == "Half Day") {
          dateValues[x] = selDate +":" + "240";
        }
        else{
          dateValues[x] = selDate +":" + "0";
	}
      }
    }
    $("#date_values", $form).val(dateValues.join('|'));
  }
{/literal}{/if}{literal}

  var countDays = 0;
  $('#tblabsence tbody:last', $form).after('<tr class="tblabsencetitle"><td>{/literal}{ts}Total{/ts}{literal}</td><td id="countD">'+countDays+'</td></tr>');
  $form.on('change','#tblabsence select', function(){
    var selectoptn = $(this).val(),
      additn = 0,
      end_date = $('#end_date_display', $form).datepicker( "getDate" ),
      start_date = $('#start_date_display', $form).datepicker( "getDate" ),
      diDate = Math.floor((end_date - start_date) / 86400000);
      totalDays=0,
      selectopt=0;
      additn = new Number(additn) + new Number(selectoptn);
    for (var x = 0; x <=diDate; x++) {
      selectopt = $('#options_'+x+' :selected', $form).val();
      totalDays = new Number(totalDays) + new Number(selectopt);
    }
    if (totalDays <= 1) {
      totalDays += ' {/literal}{ts}day{/ts}{literal}';
    }
    else {
      totalDays += ' {/literal}{ts}days{/ts}{literal}';
    }
    $('#countD', $form).html(totalDays);
  });

 // Function is used to add absence table based on selected date.
  function addabsencetbl() {
    var end_date = $('#end_date_display', $form).datepicker( "getDate" ),
      start_date = $('#start_date_display', $form).datepicker( "getDate" ),
      pubHoliday = {/literal}{$publicHolidays}{literal},
      d,
      selectedVal = [],
      earlierdate,
      absenceDate,
      sDate,
      holidayDes,
      holidayDesc,
      startDate,
      abday,
      createSelectBox,
      numberOfDaysToAdd,
      dd,
      mm,
      y,
      countDays = 0,
      diDate,
      totalDays=0,
      selectopt=0;

    if (!end_date){
      $('#end_date_display', $form).datepicker('setDate', start_date);
      end_date = $('#end_date_display', $form).datepicker( "getDate" );
    }
    if (start_date && end_date) {
      $("#tblabsence", $form).show();
      $("#commentDisplay", $form).show();
    }
    d = Math.floor((end_date - start_date) / 86400000);
    $('table#tblabsence tbody tr.trabsence', $form).remove();
    for (var x = 0; x <= d; x++) {
      earlierdate = new Date(start_date);
      absenceDate = earlierdate.toDateString();
      sDate = absenceDate.substring(4,7)+' '+earlierdate.getDate()+','+' '+earlierdate.getFullYear();
      if ( sDate in pubHoliday ) {
        holidayDes = pubHoliday[sDate];
        holidayDesc = ", "+holidayDes;
        startDate = absenceDate.substring(4,7)+' '+earlierdate.getDate()+','+' '+earlierdate.getFullYear()+' ('+absenceDate.substring(0,3)+''+holidayDesc+')';
      }
      else{
        startDate = absenceDate.substring(4,7)+' '+earlierdate.getDate()+','+' '+earlierdate.getFullYear()+' ('+absenceDate.substring(0,3)+')';
      }
      abday = absenceDate.substring(0,3);
      if ((abday == 'Sat' || abday == 'Sun') || (sDate in pubHoliday)) {
        createSelectBox = '<tr class="trabsence" ><td><label id="label_'+x+'" >'+startDate+'</label></td><td><select id="options_'+x+'" class="crm-form-select crm-select2" disabled="disabled" ><option value=""></option><option value="1">Full Day</option><option value="0.5">Half Day</option></select></td></tr>';
      }
      else {
      	createSelectBox = '<tr class="trabsence" ><td><label id="label_'+x+'" >'+startDate+'</label></td><td><select id="options_'+x+'" class="crm-form-select crm-select2"><option value="1">Full Day</option><option value="0.5">Half Day</option><option value=""></option></select></td></tr>';
      }
      $('form#AbsenceRequest table#tblabsence tbody').append(createSelectBox);
      numberOfDaysToAdd = 1;
      start_date.setDate(start_date.getDate() + numberOfDaysToAdd);
      dd = start_date.getDate();
      mm = start_date.getMonth() + 1;
      if (mm<10) mm="0"+mm;
      if (dd<10) dd="0"+dd;
      y = start_date.getFullYear();
      start_date = new Date(mm+"/"+dd+"/"+y);
      selectedVal.push(x);
    }    
    end_date = $('#end_date_display', $form).datepicker( "getDate" );
    start_date = $('#start_date_display', $form).datepicker( "getDate" );
    diDate = Math.floor((end_date - start_date) / 86400000);
    for (var x = 0; x <=diDate; x++) {
      selectopt = $('#options_'+x+' :selected', $form).val();	
      totalDays = new Number(totalDays) + new Number(selectopt);
    }
    if (totalDays <= 1) {
      totalDays += ' {/literal}{ts}day{/ts}{literal}';
    }
    else {
      totalDays += ' {/literal}{ts}days{/ts}{literal}';
    }
    $('#countD', $form).html(totalDays);
  }

});
</script>
{/literal}
