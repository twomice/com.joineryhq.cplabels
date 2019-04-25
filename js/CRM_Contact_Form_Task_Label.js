(function ($, ts) {
  // Add a unique ID to the table holding bhfe fields, so we can access it
  // directly later.
  var first_bhfe_id = CRM.vars.cplabels.bhfe_fields[0];
  $('#' + first_bhfe_id).closest('table').attr('id', 'bhfe-table');
  
  // Move bhfe fields to before price-block. ("bhfe" or "BeforeHookFormElements"
  // fields are added in this extension's buildForm hook.)
  // First create a container to hold these fields, including two separate
  // tbody elements (so two groups of fields can be hidden/displayed independently).
  for (var i in CRM.vars.cplabels.bhfe_fields) {
    $('div.crm-contact-task-mailing-label-form-block table.form-layout-compressed tbody').append(
      $('#' + CRM.vars.cplabels.bhfe_fields[i]).closest('tr')
    );
  }
  
  // Remove the bhfe table. It should be empty at this point, but clean up anyway.
  $('table#bhfe-table').remove();
  
  $('div.crm-contact-task-mailing-label-form-block').append('\n\
    <div class="help" id="cplabels_summary">' + ts('Click <em>%1</em> to view summary here ...', {1: ts('Make Mailing Labels')}) + '</div>\n\
\n\
  ');
  
  $('form#Labels').submit(function(e){
    CRM.loadPage();
  });
})(CRM.$, CRM.ts('com.joineryhq.metrotweaks'));
