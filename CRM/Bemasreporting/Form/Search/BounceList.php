<?php

class CRM_Bemasreporting_Form_Search_BounceList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  private $filterFirstName = '';
  private $filterLastName = '';
  private $filterLanguage ='';
  private $filterMembership = 0;
  private $filterFunctionCode = array();

  function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_columns = array(
      ts('Contact ID') => 'contact_a.id',
      ts("Organization Name") => "organization_name",
      ts("Email Employer") => "employer_email",
      ts("Phone") => "employer_phone",
      ts("Type of member contact") => "custom_60",
      ts("Prefix") => "prefix",
      ts('Name') => 'sort_name',
      ts("Job Title") => "job_title",
      ts("Preferred Language") => "preferred_language",
      ts("Function") => "custom_28",
      ts("Email") => "primary_email",
      ts("Phone") => "primary_phone",
    );
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Bounces'));

    $fields = array();

    $form->add('text', 'first_name', ts('First Name'), TRUE);
    $fields[] = 'first_name';

    $form->add('text', 'last_name', ts('Last Name'), TRUE);
    $fields[] = 'last_name';

    $form->addYesNo('membership', ts('Organisatie lid van BEMAS?'));
    $fields[] = 'membership';

    // add BEMAS function code list (e.g. DIRPROD)
    $form->addSelect('custom_28', array('label' => ts('Function'), 'context' => 'search', 'multiple' => TRUE));
    $fields[] = 'custom_28';

    $langOptions = array('-' => '--', 'NL' => 'Nederlands', 'FR' => 'Français', 'XX' => 'Other');
    $form->add('select', 'language', ts('Language'), $langOptions);
    $fields[] = 'language';

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

      // little hack to change the default sort order
      $defaultSort = '`contact_a`.`id` asc';
      if ($sort->orderBy() == $defaultSort) {
        $sql = str_replace($defaultSort, 'sort_field1, sort_field2, sort_field3, sort_field4', $sql);
      }
    }

    //echo $sql; exit;

    return $sql;
  }

  function select() {
    $sortFields = $this->getSortFields();

    $selectFields = "
      contact_a.id as contact_id
      , contact_a.id
      , prf.label as prefix
      , contact_a.sort_name
      , contact_a.contact_type
      , substring(contact_a.preferred_language, -2) as preferred_language
      , contact_a.first_name as first_name
      , contact_a.last_name as last_name
      , contact_a.job_title as job_title
      , contact_a.gender_id as gender_id
      , contact_email.email as primary_email
      , contact_phone.phone as primary_phone
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
      , $sortFields
    ";

    return $selectFields;
  }

  function from() {
    $from = "
      FROM
        civicrm_contact contact_a
      LEFT OUTER JOIN
        civicrm_option_value prf ON contact_a.prefix_id = prf.value AND prf.option_group_id = 6
      LEFT OUTER JOIN        
        civicrm_email contact_email ON contact_a.id = contact_email.contact_id and contact_email.is_primary = 1
      LEFT OUTER JOIN        
        civicrm_phone contact_phone ON contact_a.id = contact_phone.contact_id and contact_phone.is_primary = 1
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
    $where = "contact_a.contact_type = 'Individual' and contact_a.is_deleted = 0 and contact_email.on_hold = 1";

    $count  = 1;
    $clause = array();

    // get the filters
    $this->filterFirstName = CRM_Utils_Array::value('first_name', $this->_formValues);
    $this->filterLastName = CRM_Utils_Array::value('last_name', $this->_formValues);
    $this->filterMembership = CRM_Utils_Array::value('membership', $this->_formValues);
    $this->filterFunctionCode = CRM_Utils_Array::value('custom_28', $this->_formValues);
    $this->filterLanguage = CRM_Utils_Array::value('language', $this->_formValues);

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
      $clause[] = "exists (select * from civicrm_membership m where m.contact_id = employer.id and m.status_id in (1, 2, 3))";
    }

    if ($this->filterFunctionCode) {
      $clause[] = "civicrm_value_individual_details_19.function_28 IN ('"
        . implode("','", $this->filterFunctionCode)
        . "')";
    }

    if ($this->filterLanguage == 'NL') {
      $clause[] = "contact_a.preferred_language in ('nl_NL', 'Dutch', 'Neder')";
    }
    else if ($this->filterLanguage == 'FR') {
      $clause[] = "contact_a.preferred_language in ('fr_FR', 'Frenc', 'Franc')";
    }
    else if ($this->filterLanguage == 'XX') {
      $clause[] = "ifnull(contact_a.preferred_language, 'XX') not in ('nl_NL', 'Dutch', 'Neder', 'fr_FR', 'Frenc', 'Franc')";
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
    // default = 'CRM/Contact/Form/Search/Custom.tpl'
    return 'CRM/Bemasreporting/Form/Search/BounceList.tpl';
  }

  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

  private function getSortFields() {
    // The field we are building here will define the sort order of the organizations.
    // Because it is a field in the SQL statement that retrieves individuals, it is from that point of view
    // that it is constructed.

    // 1.
    // Check if the employer of this contact has member contacts with email on hold.
    // If the employer is NULL, we check if the person itself is a member contact.
    //
    // If there are no member contact with email on hold, we check if there are
    // MNGR, TECH, ENG, ASSET, FSM, DIRPROD with email on hold
    //
    // Result:
    //   0 if primary member contact
    //   1 if member contact
    //   2 if MNGR
    //   3 if TECH
    //   4 if ENG
    //   5 other
    $field1 = "
      if (
        employer.id IS NULL
        , if (
            civicrm_value_individual_details_19.types_of_member_contact_60 = 'M1 - Primary member contact'
            , '0'
            , if (
                civicrm_value_individual_details_19.types_of_member_contact_60 = 'MC - Member contact'
                , '1'
                , if (
                    civicrm_value_individual_details_19.types_of_member_contact_60 = 'Mx - Ex-member contact'
                    , '2'
                    , '5'
                )
              )
          )
        , if (
          (SELECT COUNT(cbounce.id) FROM civicrm_contact cbounce INNER JOIN civicrm_email ebounce ON cbounce.id = ebounce.contact_id INNER JOIN civicrm_value_individual_details_19 cibounce ON cbounce.id = cibounce.`entity_id` WHERE ebounce.on_hold = 1 AND cbounce.is_deleted = 0 AND cbounce.employer_id = employer.id AND cibounce.types_of_member_contact_60 = 'M1 - Primary member contact') > 0
          , '0'
          , if (
            (SELECT COUNT(cbounce.id) FROM civicrm_contact cbounce INNER JOIN civicrm_email ebounce ON cbounce.id = ebounce.contact_id INNER JOIN civicrm_value_individual_details_19 cibounce ON cbounce.id = cibounce.`entity_id` WHERE ebounce.on_hold = 1 AND cbounce.is_deleted = 0 AND cbounce.employer_id = employer.id AND cibounce.types_of_member_contact_60 = 'MC - Member contact') > 0
            , '1'
            , if (
              (SELECT COUNT(cbounce.id) FROM civicrm_contact cbounce INNER JOIN civicrm_email ebounce ON cbounce.id = ebounce.contact_id INNER JOIN civicrm_value_individual_details_19 cibounce ON cbounce.id = cibounce.`entity_id` WHERE ebounce.on_hold = 1 AND cbounce.is_deleted = 0 AND cbounce.employer_id = employer.id AND cibounce.types_of_member_contact_60 = 'Mx - Ex-member contact') > 0
              , '2'
              , if (
                (SELECT COUNT(cbounce.id) FROM civicrm_contact cbounce INNER JOIN civicrm_email ebounce ON cbounce.id = ebounce.contact_id INNER JOIN civicrm_value_individual_details_19 cibounce ON cbounce.id = cibounce.`entity_id` WHERE ebounce.on_hold = 1 AND cbounce.is_deleted = 0 AND cbounce.employer_id = employer.id AND cibounce.function_28 = 'MNGR') > 0
                , '3'
                , if (
                  (SELECT COUNT(cbounce.id) FROM civicrm_contact cbounce INNER JOIN civicrm_email ebounce ON cbounce.id = ebounce.contact_id INNER JOIN civicrm_value_individual_details_19 cibounce ON cbounce.id = cibounce.`entity_id` WHERE ebounce.on_hold = 1 AND cbounce.is_deleted = 0 AND cbounce.employer_id = employer.id AND cibounce.function_28 = 'TECH') > 0
                  , '4'
                  , if (
                    (SELECT COUNT(cbounce.id) FROM civicrm_contact cbounce INNER JOIN civicrm_email ebounce ON cbounce.id = ebounce.contact_id INNER JOIN civicrm_value_individual_details_19 cibounce ON cbounce.id = cibounce.`entity_id` WHERE ebounce.on_hold = 1 AND cbounce.is_deleted = 0 AND cbounce.employer_id = employer.id AND cibounce.function_28 = 'ENG') > 0
                    , '5'
                    , '6'
                  )
                )
              )
            )
          )
        )
      ) as sort_field1
    ";

    // 2.
    // add the name of the organization at the end
    $field2 = "
       ifnull(employer.organization_name, 'zzz')
       as sort_field2
    ";

    // 3.
    // Check if the person is a member contact
    $field3 = "
      if (
            civicrm_value_individual_details_19.types_of_member_contact_60 = 'M1 - Primary member contact'
            , '0'
            , if (
                civicrm_value_individual_details_19.types_of_member_contact_60 = 'MC - Member contact'
                , '1'
                , if (
                  civicrm_value_individual_details_19.types_of_member_contact_60 = 'Mx - Ex-member contact'
                  , '2'
                  , '3'
                )
              )
      ) as sort_field3
    ";

    // 4.
    // Check the function
    $field4 = "
      case ifnull(civicrm_value_individual_details_19.function_28, '')
        when 'MNGR' then 1
        when 'ASSET' then 2
        when 'ENG' then 3
        when 'FORM' then 4
        when 'INTMNGR' then 5
        when 'NRGY' then 6
        when 'SHUT' then 7
        when 'TECH' then 8
        when 'AC' then 9
        when 'BOARD' then 10
        when 'CONS' then 11
        when 'DIRPROD' then 12
        when 'FSM' then 13
        when 'RBI' then 14
        when 'VAKPERS' then 15
        when '' then 16
        else 99
      end as sort_field4
    ";

    return "$field1, $field2, $field3, $field4";
  }
}
