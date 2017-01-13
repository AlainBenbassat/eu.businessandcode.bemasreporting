<?php

class CRM_Bemasreporting_Form_Search_PersonList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  private $filterFirstName = '';
  private $filterLastName = '';
  private $filterMembership = 0;
  private $filterFunctionCode = array();
  private $filterEmailOnHold = 0;

  function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_columns = array(
      ts('Name') => 'sort_name',
      ts("Preferred Language") => "preferred_language",
      ts("First Name") => "first_name",
      ts("Last Name") => "last_name",
      ts("Job Title") => "job_title",
      ts("Gender") => "gender_id",
      ts("Email") => "primary_email",
      ts("Function") => "custom_28",
      ts("BEMAS function") => "custom_29",
      ts("Type of member contact") => "custom_60",
      ts("Type of activity (NACE)") => "custom_6",
      ts("Email Employer") => "employer_email",
      ts("Authorized number of member contacts") => "custom_73",
      ts("Website") => "url",
      ts("Membership type") => "custom_58",
      ts("Number of additional member contacts") => "custom_15",
      ts("Total Number of member contacts") => "custom_16",
      ts("Organization Name") => "organization_name",
      ts("Category # employees of membership") => "custom_13",
      ts("Number of employees") => "custom_72",
      ts("Exact Online ID") => "custom_25",
      ts("VAT number") => "custom_11",
      ts("Street Address") => "street_address",
      ts("Postal Code") => "postal_code",
      ts("City") => "city",
      ts("Country") => "country_name",
      ts("Phone") => "employer_phone",
      ts("Activity (en)") => "custom_4",
      ts("Activity (fr)") => "custom_5",
      ts("Activity (nl)") => "custom_3",
      ts("Type of activity (NACE)") => "custom_6",
    );
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Contactenlijst (personen)'));

    $fields = array();

    $form->add('text', 'first_name', ts('First Name'), TRUE);
    $fields[] = 'first_name';

    $form->add('text', 'last_name', ts('Last Name'), TRUE);
    $fields[] = 'last_name';

    $form->addYesNo('membership', ts('Lid van BEMAS?'));
    $fields[] = 'membership';

    // add BEMAS function code list (e.g. DIRPROD)
    $form->addSelect('custom_28', array('label' => ts('Function'), 'context' => 'search', 'multiple' => TRUE));
    $fields[] = 'custom_28';

    $form->addYesNo('onhold', ts('On Hold?'));
    $fields[] = 'onhold';

    $form->assign('elements', $fields);
  }

  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    if ($justIDs) {
      $sql = $this->sql('contact_a.id as contact_id', $offset, $rowcount, $sort, $includeContactIDs, NULL);
    }
    else {
      $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    }

    //echo "$sql limit 0,5;"; exit;

    return $sql;
  }

  function select() {
    $selectFields = "
      contact_a.id as contact_id
      , contact_a.sort_name
      , contact_a.contact_type
      , contact_a.preferred_language as preferred_language
      , contact_a.first_name as first_name
      , contact_a.last_name as last_name
      , contact_a.job_title as job_title
      , contact_a.gender_id as gender_id
      , contact_email.email as primary_email
      , civicrm_value_individual_details_19.function_28 as custom_28
      , civicrm_value_individual_details_19.bemas_function_29 as custom_29
      , civicrm_value_individual_details_19.types_of_member_contact_60 as custom_60
      , civicrm_value_activity_9.type_of_activity__nace__6 as custom_6
      , employer_email.email as employer_email
      , employer_country.name as country_name
      , employer_address.city as city
      , civicrm_value_membership_15.authorized_number_of_member_cont_73 as custom_73
      , employer_website.url as url
      , civicrm_value_membership_15.membership_type_58 as custom_58
      , civicrm_value_membership_15.number_of_additional_member_cont_15
      , civicrm_value_membership_15.total_number_of_member_contacts_16
      , employer.organization_name as organization_name      
      , civicrm_value_organization_details_14.category__employees_for_membersh_13 as custom_13
      , civicrm_value_organization_details_14.number_of_employees_72 as custom_72
      , civicrm_value_organization_details_14.popsy_id_25 as custom_25
      , civicrm_value_organization_details_14.vat_number_11 as custom_11      
      , employer_address.postal_code as postal_code
      , employer_address.street_address as street_address
      , employer_phone.phone as employer_phone
      , civicrm_value_activity_9.activity__nl__3 as custom_3
      , civicrm_value_activity_9.activity__en__4 as custom_4
      , civicrm_value_activity_9.activity__fr__5 as custom_5
      , civicrm_value_activity_9.type_of_activity__nace__6 as custom_6
    ";

    return $selectFields;
  }

  function from() {
    $from = "
      FROM
        civicrm_contact contact_a
      LEFT OUTER JOIN        
        civicrm_email contact_email ON contact_a.id = contact_email.contact_id and contact_email.is_primary = 1
      LEFT OUTER JOIN
        civicrm_contact employer ON employer.id = contact_a.employer_id
      LEFT OUTER JOIN        
        civicrm_email employer_email ON employer_email.contact_id = employer.id and employer_email.is_primary = 1
      LEFT OUTER JOIN        
        civicrm_address employer_address ON employer_address.contact_id = employer.id and employer_address.is_primary = 1
      LEFT OUTER JOIN
        civicrm_country employer_country ON employer_address.country_id = employer_country.id
      LEFT OUTER JOIN        
        civicrm_website employer_website ON employer_website.contact_id = employer.id and employer_website.website_type_id = 1
      LEFT OUTER JOIN        
        civicrm_phone employer_phone ON employer_phone.contact_id = employer.id and employer_phone.is_primary = 1
      LEFT OUTER JOIN        
        civicrm_value_individual_details_19 ON civicrm_value_individual_details_19.entity_id = contact_a.id 
      LEFT OUTER JOIN        
        civicrm_value_activity_9 ON civicrm_value_activity_9.entity_id = employer.id
      LEFT OUTER JOIN        
        civicrm_value_membership_15 ON civicrm_value_membership_15.entity_id = employer.id
      LEFT OUTER JOIN        
        civicrm_value_organization_details_14 ON civicrm_value_organization_details_14.entity_id = employer.id
    ";

    // add ACL
    $this->buildACLClause('contact_a');
    $from .= " {$this->_aclFrom} ";

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    $params = array();
    $where = "contact_a.contact_type = 'Individual' and contact_a.is_deleted = 0";

    $count  = 1;
    $clause = array();

    // get the filters
    $this->filterFirstName = CRM_Utils_Array::value('first_name', $this->_formValues);
    $this->filterLastName = CRM_Utils_Array::value('last_name', $this->_formValues);
    $this->filterMembership = CRM_Utils_Array::value('membership', $this->_formValues);
    $this->filterFunctionCode = CRM_Utils_Array::value('custom_28', $this->_formValues);
    $this->filterEmailOnHold = CRM_Utils_Array::value('onhold', $this->_formValues);

    if ($this->filterFirstName) {
      $params[$count] = array($this->filterFirstName, 'String');

      if (strpos($this->filterFirstName, '%') === FALSE) {
        $clause[] = "contact_a.first_name = %{$count}";
      }
      else {
        $clause[] = "contact_a.first_name LIKE %{$count}";
      }

      $count++;
    }

    if ($this->filterLastName) {
      $params[$count] = array($this->filterLastName, 'String');

      if (strpos($this->filterLastName, '%') === FALSE) {
        $clause[] = "contact_a.last_name = %{$count}";
      }
      else {
        $clause[] = "contact_a.last_name LIKE %{$count}";
      }

      $count++;
    }

    if ($this->filterMembership == 1) {
      $clause[] = "exists (select * from civicrm_membership m where m.contact_id = contact_a.id and m.status_id in (1, 2, 3))";
    }

    if ($this->filterFunctionCode) {
      $clause[] = "civicrm_value_individual_details_19.function_28 IN ('"
        . implode("','", $this->filterFunctionCode)
        . "')";
    }

    if ($this->filterEmailOnHold == 1) {
      $clause[] = "employer_email.on_hold = 1";
    }

    // add ACL
    if ($this->_aclWhere) {
      $clause[] = "{$this->_aclWhere}";
    }

    if (!empty($clause)) {
      $where .= ' AND ' . implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }
}
