<?php
use CRM_Cplabels_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Cplabels_Form_Search_Cplabels_Client extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Override parent::sql().
   *
   * We're letting where() and other methods work together to build a subselect,
   * but we need to wrap it into the final query here.
   *
   * @inerhitdoc
   */
  public function sql(
    $selectClause,
    $offset = 0,
    $rowcount = 0,
    $sort = NULL,
    $includeContactIDs = FALSE,
    $groupBy = NULL
  ) {


    $subselect = "SELECT
      DISTINCT
      contact_a.id as contact_id,
      contact_a.sort_name as sort_name
      " . $this->from();
    $where = $this->where();
    if (!empty($where)) {
      $subselect .= " WHERE " . $where;
    }

    $sql = "SELECT $selectClause
      FROM (
        SELECT DISTINCT
          COALESCE(r_mail.contact_id_b, t.contact_id) as id,
          COALESCE(c_mail.sort_name, t.sort_name) as sort_name,
          address.street_address,
          address.city,
          state_province.name as state_province,
          address.postal_code,
          county.name as county
        FROM
          ($subselect) as t
          LEFT JOIN civicrm_relationship r_mail
          -- FIXME: don't hardcode 14 here.
            ON r_mail.contact_id_a = t.contact_id and r_mail.relationship_type_id = '14'
                AND r_mail.is_active
                AND IFNULL(r_mail.end_date, CURDATE()) >= CURDATE()
          LEFT JOIN civicrm_contact c_mail ON c_mail.id = r_mail.contact_id_b
          LEFT JOIN civicrm_address address ON (address.contact_id = COALESCE(r_mail.contact_id_b, t.contact_id) AND address.is_primary = 1)
          LEFT JOIN civicrm_state_province state_province ON state_province.id = address.state_province_id
          LEFT JOIN civicrm_county county ON county.id = address.county_id
      ) contact_a
      WHERE ( 1 )
    ";

    if ($includeContactIDs) {
      $this->includeContactIDs($sql,
        $this->_formValues
      );
    }

    if ($groupBy) {
      $sql .= " $groupBy ";
    }

    $this->addSortOffset($sql, $offset, $rowcount, $sort);

    return $sql;
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

    // add select for diagnoses
    $custom_id = CRM_Cplabels_Utils::getCustomFieldProp('Diagnosis_1', 'Health');
    $diagnosis = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', "custom_{$custom_id}", array(), 'search');
    $form->addElement('select', 'diagnosis', ts('Diagnosis'), $diagnosis, array('class' => 'crm-select2 huge', 'multiple' => TRUE));

    // Add select for communication preference.
    $limitOptions = array(
      'mail_preferred' => ts('Print only those who prefer mail'),
      'all' => ts('Print all'),
    );
    $form->addElement('select', 'limit', ts('Limit'), $limitOptions, array('class' => 'crm-select2 huge'));

    // Add fields for age
    $form->addElement('text', 'min_age', ts('Minimum age'));
    $form->addElement('text', 'max_age', ts('Maximum age'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('team', 'diagnosis', 'min_age', 'max_age', 'limit'));
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
      E::ts('Name') => 'sort_name',
      E::ts('Address') => 'street_address',
      E::ts('City') => 'city',
      E::ts('State') => 'state_province',
      E::ts('Postal Code') => 'postal_code',
      E::ts('County') => 'county',
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
    $sort = 'contact_a.sort_name';
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
      sort_name,
      street_address,
      city,
      state_province,
      postal_code,
      county
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {

    $customTableNameHealth = CRM_Cplabels_Utils::getCustomGroupProp('Health');
    $customTableNameParticipation = CRM_Cplabels_Utils::getCustomGroupProp('Participation');

    // NOTE: This query is wrapped as a subselect in all().
    return "
      FROM
        civicrm_contact contact_a
        INNER JOIN civicrm_relationship r
          -- fixme: don't hardcode 17 here
          ON r.contact_id_b = contact_a.id and r.relationship_type_id = '18'
          AND r.is_active
          AND IFNULL(r.end_date, CURDATE()) >= CURDATE()
        INNER JOIN $customTableNameHealth vhealth ON vhealth.entity_id = contact_a.id
        INNER JOIN $customTableNameParticipation vpart ON vpart.entity_id = contact_a.id
    ";


  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string|void
   */
  public function where($includeContactIDs = FALSE) {
    $customColumnNameDispositionDate = CRM_Cplabels_Utils::getCustomFieldProp('Disposition_Date', 'Participation', 'column_name');
    $whereClause = " (
        NOT IFNULL(contact_a.is_deceased, 0)
        AND NOT IFNULL(contact_a.is_deleted, 0)
        AND IFNULL(vpart.{$customColumnNameDispositionDate}, now()) >= now()
        AND contact_a.contact_sub_type = 'client'
      )
    ";

    if ($team = CRM_Utils_Array::value('team', $this->_formValues)) {
      $team = CRM_Cplabels_Utils::arrayToSqlInValues($team, 'Int');
      $whereClause .= "
        AND r.contact_id_a IN ($team)
      ";
    }

    // Diagnosis
    if ($diagnosis = CRM_Utils_Array::value('diagnosis', $this->_formValues)) {
      $diagnosis = CRM_Cplabels_Utils::arrayToSqlInValues($diagnosis, 'String');
      $customColumnName1 = CRM_Cplabels_Utils::getCustomFieldProp('Diagnosis_1', 'Health', 'column_name');
      $customColumnName2 = CRM_Cplabels_Utils::getCustomFieldProp('Diagnosis_2', 'Health', 'column_name');
      $customColumnName3 = CRM_Cplabels_Utils::getCustomFieldProp('Diagnosis_3', 'Health', 'column_name');
      $whereClause .= "
        AND (
          vhealth.$customColumnName1 IN ($diagnosis)
          OR vhealth.$customColumnName2 IN ($diagnosis)
          OR vhealth.$customColumnName3 IN ($diagnosis)
        )
      ";
    }

    // Min Age
    if ($min_age = CRM_Utils_Array::value('min_age', $this->_formValues)) {
      $dao = new CRM_Core_DAO();
      $min_age = $dao->escape($min_age);
      $whereClause .= "
        AND (
          (TIMESTAMPDIFF(YEAR, birth_date, CURDATE())) >= '{$min_age}'
        )
      ";
    }

    // Max Age
    if ($max_age = CRM_Utils_Array::value('max_age', $this->_formValues)) {
      $dao = new CRM_Core_DAO();
      $max_age = $dao->escape($max_age);
      $whereClause .= "
        AND (
          (TIMESTAMPDIFF(YEAR, birth_date, CURDATE())) <= '{$max_age}'
        )
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
