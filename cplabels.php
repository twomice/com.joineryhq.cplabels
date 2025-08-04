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
  if ($formName == 'CRM_Labelsort_Contact_Form_Task_Label_Sortable') {
    $qfKey = $form->controller->_key;
    $customSearchSessionValues = CRM_Cplabels_Utils::getSessionVar("formValues_{$qfKey}", array());
    // Pass some of these values to JavaScript.
    // $customSearchClass is needed to show/hide "Team Name" sort option.
    // We could do it here, but this hook may fire before the labelsort extension's hook,
    // in which case the "Sort" field won't even exist.
    $vars = array();
    $vars['customSearchClass'] = CRM_Utils_Array::value('customSearchClass', $customSearchSessionValues);;
    CRM_Core_Resources::singleton()->addVars('cplabels', $vars);
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.cplabels', 'js/CRM_Labelsort_Contact_Form_Task_Label_Sortable.js', 100, 'page-footer');
  }
}

/**
 * Implements hook_civicrm_alterMailingLabelRows().
 *
 * @link https://github.com/twomice/com.joineryhq.labelsort/blob/master/README.md
 *
 */
function cplabels_civicrm_alterMailingLabelRows(&$rows, $formValues) {
  CRM_Cplabels_Utils::sortLabelRows($rows, $formValues);
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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function cplabels_civicrm_install() {
  _cplabels_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function cplabels_civicrm_enable() {
  _cplabels_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 */
// function cplabels_civicrm_preProcess($formName, &$form) {

// } // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
// function cplabels_civicrm_navigationMenu(&$menu) {
//   _cplabels_civix_insert_navigation_menu($menu, 'Mailings', array(
//     'label' => E::ts('New subliminal message'),
//     'name' => 'mailing_subliminal_message',
//     'url' => 'civicrm/mailing/subliminal',
//     'permission' => 'access CiviMail',
//     'operator' => 'OR',
//     'separator' => 0,
//   ));
//   _cplabels_civix_navigationMenu($menu);
// } // */
