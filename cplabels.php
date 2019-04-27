<?php

require_once 'cplabels.civix.php';
use CRM_Cplabels_ExtensionUtil as E;

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function cplabels_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_Search_Custom') {
    $supportedSearches = array(
      'CRM_Cplabels_Form_Search_Cplabels_Volunteer',
      'CRM_Cplabels_Form_Search_Cplabels_CommonGround',
      'CRM_Cplabels_Form_Search_Cplabels_Client',
    );
    
    if (in_array($form->getVar('_customSearchClass'), $supportedSearches)) {
      // This is one of our custom searches for carepartners labels. Cache the
      // form values in the session.
      if ($qfKey = CRM_Utils_Array::value('qfKey', $form->_formValues)) {
        CRM_Cplabels_Utils::setSessionVar("formValues_{$qfKey}", $form->_formValues);
      }
    }
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function cplabels_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_Task_Label') {
    $qfKey = $form->controller->_key;
    $customSearchSessionValues = CRM_Cplabels_Utils::getSessionVar("formValues_{$qfKey}", array());
    if (!empty($customSearchSessionValues)) {
      // We're coming directly from one of our custom search for carepartners labels.
      // Add our extra "sort" field and anytyhing else we need here.
      $sortOptions = array(
        'sort_name' => ts('Contact Name'),
        'postal_code' => ts('Postal Code'),
      );

      // Allow sort by Team Name on certain label searches only.
      $customSearchClass = CRM_Utils_Array::value('customSearchClass', $customSearchSessionValues);
      $sortTeamSearches = array (
        'CRM_Cplabels_Form_Search_Cplabels_Volunteer',
        'CRM_Cplabels_Form_Search_Cplabels_Client',
      );
      if (in_array($customSearchClass, $sortTeamSearches)) {
        $sortOptions['team_name'] = ts('Team Name');
      }

      $form->add('select', 'cplabel_sort', ts('Sort by'), $sortOptions);

      // Assign bhfe fields to the template.
      $tpl = CRM_Core_Smarty::singleton();
      $bhfe = (array)$tpl->get_template_vars('beginHookFormElements');
      $bhfe[] = 'cplabel_sort';
      $form->assign('beginHookFormElements', $bhfe);

      // Pass some of these values to JavaScript.
      $vars = array();
      $vars['bhfe_fields'] = $bhfe;
      CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.cplabels', 'js/CRM_Contact_Form_Task_Label.js', 100, 'page-footer');
      CRM_Core_Resources::singleton()->addVars('cplabels', $vars);
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function cplabels_civicrm_config(&$config) {
  _cplabels_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function cplabels_civicrm_xmlMenu(&$files) {
  _cplabels_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function cplabels_civicrm_install() {
  _cplabels_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function cplabels_civicrm_postInstall() {
  _cplabels_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function cplabels_civicrm_uninstall() {
  _cplabels_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function cplabels_civicrm_enable() {
  _cplabels_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function cplabels_civicrm_disable() {
  _cplabels_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function cplabels_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _cplabels_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function cplabels_civicrm_managed(&$entities) {
  _cplabels_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function cplabels_civicrm_caseTypes(&$caseTypes) {
  _cplabels_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function cplabels_civicrm_angularModules(&$angularModules) {
  _cplabels_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function cplabels_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _cplabels_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function cplabels_civicrm_entityTypes(&$entityTypes) {
  _cplabels_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function cplabels_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function cplabels_civicrm_navigationMenu(&$menu) {
  _cplabels_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _cplabels_civix_navigationMenu($menu);
} // */
