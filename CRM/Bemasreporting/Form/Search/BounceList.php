<?php

class CRM_Bemasreporting_Form_Search_BounceList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  private $filterFirstName = '';
  private $filterLastName = '';
  private $filterLanguage ='';
  private $filterMembership = 0;
  private $filterFunctionCode = [];

  function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_columns = [
      ts('Contact ID') => 'contact_a.id',
      ts('Organization Name') => 'organization_name',
      ts('Email Employer') => 'employer_email',
      ts('Phone') => 'employer_phone',
      ts('Type of member contact') => 'custom_60',
      ts('Prefix') => 'prefix',
      ts('Name') => 'sort_name',
      ts('Job Title') => 'job_title',
      ts('Preferred Language') => 'preferred_language',
      ts('Function') => 'custom_28',
      ts('Email') => 'primary_email',
      ts('Phone') => 'primary_phone',
      ts('Bounce datum') => 'primary_email_hold_date',
    ];
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Bounces'));

    $fields = [];

    $form->add('text', 'first_name', ts('First Name'), []);
    $fields[] = 'first_name';

    $form->add('text', 'last_name', ts('Last Name'), []);
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
    }

    //die($sql);

    return $sql;
  }

  function select() {
    $typeOfMemberContact = $this->getTypeOfMemberContact();

    $selectFields = "
      contact_a.id as contact_id
      , contact_a.id
      , prf.label as prefix
      , contact_a.sort_name
      , substring(contact_a.preferred_language, -2) as preferred_language
      , contact_a.first_name as first_name
      , contact_a.last_name as last_name
      , contact_a.job_title as job_title
      , contact_email.email as primary_email
      , contact_phone.phone as primary_phone
      , civicrm_value_individual_details_19.function_28 as custom_28
      , civicrm_value_individual_details_19.bemas_function_29 as custom_29
      , $typeOfMemberContact as custom_60
      , employer_email.email as employer_email
      , employer.organization_name as organization_name
      , contact_email.hold_date primary_email_hold_date
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
        civicrm_phone employer_phone ON employer_phone.contact_id = employer.id and employer_phone.is_primary = 1
      LEFT OUTER JOIN
        civicrm_value_individual_details_19 ON civicrm_value_individual_details_19.entity_id = contact_a.id
      LEFT OUTER JOIN
        civicrm_value_activity_9 ON civicrm_value_activity_9.entity_id = employer.id
      LEFT OUTER JOIN
        civicrm_value_organization_details_14 ON civicrm_value_organization_details_14.entity_id = employer.id
    ";

    // add ACL
    $this->buildACLClause('contact_a');
    $from .= " {$this->_aclFrom} ";

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    $params = [];
    $where = "
        contact_a.contact_type = 'Individual'
      and
        contact_a.is_deleted = 0
      and
        contact_email.on_hold = 1
    ";

    $count  = 1;
    $clause = [];

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
      $STATUS_NEW = 1;
      $STATUS_CURRENT = 2;
      $STATUS_GRACE = 3;
      $STATUS_RESIGNING = 10;
      $clause[] = "exists (select * from civicrm_membership m where m.contact_id = employer.id and m.status_id in ($STATUS_NEW, $STATUS_CURRENT, $STATUS_GRACE, $STATUS_RESIGNING))";
    }

    if ($this->filterFunctionCode) {
      $clause[] = "civicrm_value_individual_details_19.function_28 IN ('"
        . implode("','", $this->filterFunctionCode)
        . "')";
    }

    if ($this->filterLanguage == 'NL') {
      $clause[] = "contact_a.preferred_language in ('nl_NL', 'Dutch', 'Neder')";
    }
    elseif ($this->filterLanguage == 'FR') {
      $clause[] = "contact_a.preferred_language in ('fr_FR', 'Frenc', 'Franc')";
    }
    elseif ($this->filterLanguage == 'XX') {
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
    return 'CRM/Bemasreporting/Form/Search/BounceList.tpl';
  }

  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

  private function getTypeOfMemberContact() {
    $sql = "
      CASE
        WHEN (select max(m1.id) from civicrm_relationship m1 where m1.relationship_type_id = 14 and m1.is_active = 1 and m1.contact_id_a = contact_a.id) IS NOT NULL THEN 'M1'
        WHEN (select max(mc.id) from civicrm_relationship mc where mc.relationship_type_id = 15 and mc.is_active = 1 and mc.contact_id_a = contact_a.id) IS NOT NULL THEN 'MC'
        WHEN (select max(mx.id) from civicrm_relationship mx where mx.relationship_type_id in (14, 15) and mx.is_active = 0 and mx.contact_id_a = contact_a.id) IS NOT NULL THEN 'Mx'
        ELSE
        ''
      END
    ";

    return $sql;
  }
}
