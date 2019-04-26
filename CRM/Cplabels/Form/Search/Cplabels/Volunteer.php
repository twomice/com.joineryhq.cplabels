<?php
use CRM_Cplabels_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Cplabels_Form_Search_Cplabels_Volunteer extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
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
    $team = array();
    foreach ($result['values'] as $value) {
      $team[$value['contact_id']] = $value['display_name'];
    }
    $form->addElement('select', 'team', ts('Team'), $team, array('class' => 'crm-select2 huge', 'multiple' => TRUE));

    // add select for service types
    $custom_id = CRM_Cplabels_Utils::getCustomFieldProp('Service_Type', 'Volunteer_details');
    $serveType = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', "custom_{$custom_id}", array(), 'search');
    $form->addElement('select', 'serve_type', ts('Service type'), $serveType, array('class' => 'crm-select2 huge', 'multiple' => TRUE));

    // Add select for communication preference.
    $limitOptions = array(
      'mail_preferred' => ts('Print only those who prefer mail'),
      'all' => ts('Print all'),
    );
    $form->addElement('select', 'limit', ts('Limit'), $limitOptions, array('class' => 'crm-select2 huge'));

    // Add selects for correspondence types.
    $custom_id = CRM_Cplabels_Utils::getCustomFieldProp('Correspondence_types', 'Communication');
    $correspondenceTypeOptions = array('' => '') + CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', "custom_{$custom_id}", array(), 'search');
    $form->addElement('select', 'correspondence_type', ts('Correspondence type'), $correspondenceTypeOptions, array('class' => 'crm-select2 huge'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('serve_type', 'team', 'limit', 'correspondence_type'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      E::ts('Name') => 'display_name',
      E::ts('Address') => 'street_address',
      E::ts('City') => 'city',
      E::ts('State') => 'state_province',
      E::ts('Postal Code') => 'postal_code',
      E::ts('County') => 'county',
      E::ts('email') => 'email',
      E::ts('phone') => 'phone',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    $sort = 'contact_a.sort_name DESC';
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    return $sql;
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      DISTINCT
      contact_a.id as contact_id,
      contact_a.display_name as display_name,
      address.street_address,
      address.city,
      state_province.name as state_province,
      address.postal_code,
      county.name as county,
      email.email,
      phone.phone
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {

    $customTableNameVolunteer = CRM_Cplabels_Utils::getCustomGroupProp('Volunteer_details');
    $customTableNameCommunications = CRM_Cplabels_Utils::getCustomGroupProp('Communication');

    return "
      FROM
        civicrm_contact contact_a
        INNER JOIN civicrm_relationship r 
          ON r.contact_id_b = contact_a.id and r.relationship_type_id = '17'
          AND r.is_active
          AND IFNULL(r.end_date, CURDATE()) >= CURDATE()
        INNER JOIN $customTableNameVolunteer vvd ON vvd.entity_id = r.id
        LEFT JOIN $customTableNameCommunications vcom ON vcom.entity_id = contact_a.id
        LEFT JOIN civicrm_email email ON (email.contact_id = contact_a.id AND email.is_primary = 1)
        LEFT JOIN civicrm_phone phone ON (phone.contact_id = contact_a.id AND phone.is_primary = 1)
        LEFT JOIN civicrm_address address ON (address.contact_id = contact_a.id AND address.is_primary = 1)
        LEFT JOIN civicrm_state_province state_province ON state_province.id = address.state_province_id
        LEFT JOIN civicrm_county county ON county.id = address.county_id
    ";
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string|void
   */
  public function where($includeContactIDs = FALSE) {
    $whereClause = ' (
        NOT IFNULL(contact_a.is_deceased, 0)
        AND NOT IFNULL(contact_a.is_deleted, 0)
      )
    ';

    if ($team = CRM_Utils_Array::value('team', $this->_formValues)) {
      $team = CRM_Cplabels_Utils::arrayToSqlInValues($team, 'Int');
      $whereClause .= "
        AND r.contact_id_a IN ($team)
      ";
    }

    if ($serve_type = CRM_Utils_Array::value('serve_type', $this->_formValues)) {
      $serve_type = CRM_Cplabels_Utils::arrayToSqlInValues($serve_type, 'String');
      $customColumnName = CRM_Cplabels_Utils::getCustomFieldProp('Service_Type', 'Volunteer_details', 'column_name');
      $whereClause .= "
        AND vvd.$customColumnName IN ($serve_type)
      ";
    }

    if ($correspondence_type = CRM_Utils_Array::value('correspondence_type', $this->_formValues)) {
      if ($correspondence_type == 'N') {
        $customColumnName = CRM_Cplabels_Utils::getCustomFieldProp('Correspondence_types', 'Communication', 'column_name');
        $or_empty = " OR vcom.$customColumnName = '' ";
      }
      else {
        $or_empty = "";
      }
      $customColumnName = CRM_Cplabels_Utils::getCustomFieldProp('Correspondence_types', 'Communication', 'column_name');
      $whereClause .= "
        AND ( IFNULL(vcom.$customColumnName, 'N') = '$correspondence_type' $or_empty )
      ";
    }

    if ($limit = CRM_Utils_Array::value('limit', $this->_formValues)) {
      if ($limit == 'mail_preferred') {
        $value = CRM_Utils_Array::implodePadded(array(3));
        $whereClause .= "
          AND contact_a.preferred_communication_method like '%$value%'
        ";
      }
    }
    return $whereClause;
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
  }

}
