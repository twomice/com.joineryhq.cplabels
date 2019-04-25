<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Cplabels_Form_Search_Cplabels extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_query;
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_columns = array(
      '' => 'contact_type',
      ts('Name') => 'sort_name',
      ts('Address') => 'street_address',
      ts('City') => 'city',
      ts('State') => 'state_province',
      ts('Postal') => 'postal_code',
      ts('Country') => 'country',
      ts('Email') => 'email',
      ts('Phone') => 'phone',
    );

    $params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $returnProperties = array();
    $returnProperties['contact_sub_type'] = 1;

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    foreach ($this->_columns as $name => $field) {
      if (in_array($field, array(
          'street_address',
          'city',
          'state_province',
          'postal_code',
          'country',
        )) && empty($addressOptions[$field])
      ) {
        unset($this->_columns[$name]);
        continue;
      }
      $returnProperties[$field] = 1;
    }

    $this->_query = new CRM_Contact_BAO_Query($params, $returnProperties, NULL,
      FALSE, FALSE, 1, FALSE, FALSE
    );
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    // add select for team
    $api_params = array(
      'contact_sub_type' => 'team',
      'options' => array(
        'limit' => 0,
        'sort' => 'display_name',
      ),
      'return' => array(
        'display_name',
      ),
    );
    $result = civicrm_api3('contact', 'get', $api_params);
    $team = array('' => ts('- any team -'));
    foreach ($result['values'] as $value) {
      $team[$value['contact_id']] = $value['display_name'];
    }
    $form->addElement('select', 'team', ts('Team'), $team, array('class' => 'crm-select2 huge', 'multiple' => TRUE));

    // add select for service types
    $serveType = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_58', array(), 'search');
    $form->addElement('select', 'serve_type', ts('Service type'), $serveType, array('class' => 'crm-select2 huge', 'multiple' => TRUE));
  }

  /**
   * @return CRM_Contact_DAO_Contact
   */
  public function count() {
    return $this->_query->searchQuery(0, 0, NULL, TRUE);
  }

  /**
   * @param int $offset
   * @param int $rowCount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return CRM_Contact_DAO_Contact
   */
  public function all(
    $offset = 0,
    $rowCount = 0,
    $sort = '', //sort_name',
    $includeContactIDs = FALSE,
    $justIDs = FALSE
  ) {
    $sort = 'civicrm_address.city';
    $sql = $this->_query->searchQuery(
      $offset,
      $rowCount,
      $sort,
      FALSE,
      $includeContactIDs,
      FALSE,
      $justIDs,
      TRUE
    );
    dsm($sql, 'sql');
    return $sql;
  }

  /**
   * @return string
   */
  public function from() {
    $this->buildACLClause('contact_a');
    $from = $this->_query->_fromClause;
    $from .= "{$this->_aclFrom}";
    return $from;
  }

  private static function _array_to_sql_in_values($arr = array(), $emptyValue = "''") {
    if (empty($arr)) {
      return $emptyValue;
    }

    $placeholders = $params = [];

    $i = 1;
    foreach ($arr as $value){
      $placeholders[] = "'%$i'";
      $params[$i] = array($value, 'Int');
      $i++;
    }
    if (!empty($placeholders)) {
      $query = implode(', ', $placeholders);
    }
    dsm($query, '$query');
    dsm($params, 'params');
    return CRM_Core_DAO::composeQuery($query, $params);
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string|void
   */
  public function where($includeContactIDs = FALSE) {
    if ($whereClause = $this->_query->whereClause()) {
      if ($this->_aclWhere) {
        $whereClause .= " AND {$this->_aclWhere}";
      }
    }
    else {
      $whereClause = ' (1) ';
    }

    if ($team = CRM_Utils_Array::value('team', $this->_formValues)) {
      $team = self::_array_to_sql_in_values($team);
      $whereClause .= "
        AND contact_a.id IN ($team)
      ";
    }
    dsm($whereClause, '$whereClause');
    return $whereClause;
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Cplabels/Form/Search/Cplabels.tpl';
  }

  /**
   * @return CRM_Contact_BAO_Query
   */
  public function getQueryObj() {
    return $this->_query;
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
