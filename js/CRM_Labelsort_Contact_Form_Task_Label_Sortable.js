(function ($, ts) {
  
  var submittedFormData;
  
  // Remove 'team name' sort option, if so configured.
  if (CRM.vars.cplabels.customSearchClass != 'CRM_Cplabels_Form_Search_Cplabels_Volunteer') {
    $('select#labelsort_sort option[value="volunteer_team_name"]').remove();
  }
  if (CRM.vars.cplabels.customSearchClass != 'CRM_Cplabels_Form_Search_Cplabels_Client') {
    $('select#labelsort_sort option[value="client_team_name"]').remove();
  }
 
  if (CRM.vars.cplabels.customSearchClass) {

    // Append "view summary" button, disabled.
    $('div.crm-contact-task-mailing-label-form-block').append('\n\
  <fieldset>\n\
      <h3><span class="crm_blocktitle">' + ts('Labels summary') + '</span></h3>\n\
  <button id="cplabels_load_summary" disabled="disabled">Display summary for most recently downloaded labels</button></fieldset>\n\
  \n\
    ');

    // On form submit, enable the 'view summary' button, and compile form data,
    // so we can submit it as a key with the 'view summary' button.
    $('form#Sortable').submit(function(e){
      $('button#cplabels_load_summary').enable();
      submittedFormData = $('form#Sortable').serialize();      
    });

    // Deinfe click handler for 'view summary' button.
    $('button#cplabels_load_summary').click(function(e){
      CRM.loadPage(CRM.url('civicrm/cplabels/labelsummary?' + submittedFormData));
      e.preventDefault();
    });
  }
})(CRM.$, CRM.ts('com.joineryhq.metrotweaks'));
