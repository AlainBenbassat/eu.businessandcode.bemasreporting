<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * A custom contact search
 */
class CRM_Bemasreporting_Form_Search_MemberList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Ledenlijst'));
  }

  function summary() {
    return NULL;
  }

  function &columns() {
    // return by reference
    $columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Street') => 'street_address',
      ts('Supplemental Address 1') => 'supplemental_address_1',
      ts('Supplemental Address 2') => 'supplemental_address_2',
      ts('Postal Code') => 'postal_code',
      ts('City') => 'city',
      ts('Country') => 'country_name',
      ts('Phone') => 'phone',
      ts('Email') => 'email',
      ts('Website') => 'url',
      ts('Contacts') => 'member_contacts',
      ts('Description NL') => 'description_nl',
      ts('Description EN') => 'description_en',
      ts('Description FR') => 'description_fr',
      ts('NACE') => 'nace_code',
    );
    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    //die($sql);
    return $sql;
  }

  function select() {
    $select = "
      contact_a.id
      , contact_a.id as contact_id
      , contact_a.sort_name as sort_name
      , a.street_address
      , a.supplemental_address_1
      , a.supplemental_address_2
      , a.postal_code
      , a.city
      , ctry.iso_code country_iso_code
      , ctry.name country_name
      , p.phone
      , e.email
      , w.url
      , act.activity__nl__3 description_nl
      , act.activity__en__4 description_en
      , act.activity__fr__5 description_fr      
      , '' as member_contacts
      , nace.label nace_code
    ";

    return $select;
  }

  function from() {
    return "
      FROM     
        civicrm_contact contact_a
      inner join
        civicrm_membership m on m.contact_id = contact_a.id
      left outer join 
        civicrm_address a on a.contact_id = contact_a.id and a.is_primary = 1
      left outer join 
        civicrm_country ctry on a.country_id = ctry.id
      left outer join 
        civicrm_email e on e.contact_id = contact_a.id and e.is_primary = 1
      left outer join 
        civicrm_phone p on p.contact_id = contact_a.id and p.is_primary = 1
      left outer join 
        civicrm_website w on w.contact_id = contact_a.id
      left outer join 
        civicrm_value_activity_9 act on act.entity_id = contact_a.id
      left outer join
        civicrm_option_value nace on act.type_of_activity__nace__6 = nace.value and nace.option_group_id = 85
    ";
  }

  function where($includeContactIDs = FALSE) {
    $params = array();

    $where = "
      contact_a.contact_type = 'Organization'
      and m.owner_membership_id IS NULL
      and m.status_id = 1
    ";

    return $this->whereClause($where, $params);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function alterRow(&$row) {
    $contactList = [];

    // get member contacts
    $sql = "
      select
        c.first_name
        , c.last_name
        , c.job_title
        , e.email
      from
        civicrm_contact c
      left outer join 
        civicrm_value_individual_details_19 det on det.entity_id = c.id
      left outer join 
        civicrm_email e on e.contact_id = c.id and e.is_primary = 1
      where 
        c.employer_id = %1
      and
        types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact')
    ";
    $sqlParams = [
      1 => [$row['contact_id'], 'Integer'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    while ($dao->fetch()) {
      $contact = $dao->first_name . ' ' . $dao->last_name;

      if ($dao->job_title) {
        $contact .= ', ' . $dao->job_title;
      }

      $contactList[] = $contact;
    }

    $row['member_contacts'] = implode('|', $contactList);
  }
}
