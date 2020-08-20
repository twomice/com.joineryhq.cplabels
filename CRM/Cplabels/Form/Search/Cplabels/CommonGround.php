<?php
use CRM_Cplabels_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Cplabels_Form_Search_Cplabels_CommonGround extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {

    // Define a custom title for the search form.
    $this->setTitle(ts('Mailing Labels: Common Ground'));

    // This search has no editable criteria.
    $form->addElement('static', 'statichtml', '', '<p class="status alert">' . E::ts('This search has no editable criteria. Please click "Search".') . '</p>');
    $form->assign('elements', array('statichtml'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed;
   *   NULL or array with keys:
   */
  public function summary() {
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
  public function &columns() {
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
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
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
  public function select() {
    return "
      DISTINCT
      contact_a.id as contact_id,
      contact_a.sort_name,
      address.street_address,
      address.city,
      state_province.name as state_province,
      address.postal_code,
      county.name as county
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  public function from() {

    $customTableNameParticipation = CRM_Cplabels_Utils::getCustomGroupProp('Participation');
    $customTableNameHealth = CRM_Cplabels_Utils::getCustomGroupProp('Health');

    return "
      FROM
        civicrm_contact contact_a
        INNER JOIN $customTableNameParticipation vpart ON vpart.entity_id = contact_a.id
        INNER  JOIN $customTableNameHealth vhealth ON vhealth.entity_id = contact_a.id
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

    $customColumnNameDisposition = CRM_Cplabels_Utils::getCustomFieldProp('Disposition_Date', 'Participation', 'column_name');
    $customColumnNameCG = CRM_Cplabels_Utils::getCustomFieldProp('Common_Ground', 'Health', 'column_name');

    $whereClause = " (
        NOT IFNULL(contact_a.is_deceased, 0)
        AND NOT IFNULL(contact_a.is_deleted, 0)
        AND IFNULL(vpart.{$customColumnNameDisposition}, now()) >= now()
        AND vhealth.{$customColumnNameCG}
      )
    ";
    return $whereClause;
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  public function alterRow(&$row) {
  }

}
