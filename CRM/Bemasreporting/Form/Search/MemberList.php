<?php

class CRM_Bemasreporting_Form_Search_MemberList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Ledenlijst'));

    $fields = [];
    $defaults = [];

    $form->addYesNo('inherited_membership', ts('Including subsidiaries?'), FALSE, FALSE);
    $defaults['inherited_membership'] = 1;
    $fields[] = 'inherited_membership';

    $form->assign('elements', $fields);
    $form->setDefaults($defaults);
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
      , '' url
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
        civicrm_value_activity_9 act on act.entity_id = contact_a.id
      left outer join
        civicrm_option_value nace on act.type_of_activity__nace__6 = nace.value and nace.option_group_id = 85
    ";
  }

  function where($includeContactIDs = FALSE) {
    $STATUS_NEW = 1;
    $STATUS_CURRENT = 2;
    $STATUS_GRACE = 3;
    $STATUS_RESIGNING = 10;
    $filterInheritedMembership = CRM_Utils_Array::value('inherited_membership', $this->_formValues);
    $params = [];

    $where = "
      is_deleted = 0
      and m.status_id in ($STATUS_NEW, $STATUS_CURRENT, $STATUS_GRACE, $STATUS_RESIGNING)
    ";

    if ($filterInheritedMembership == 1) {
      $where .= " and (m.owner_membership_id IS NULL or contact_a.contact_type = 'Organization')";
    }
    else {
      $where .= ' and m.owner_membership_id IS NULL ';
    }




    return $this->whereClause($where, $params);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function alterRow(&$row) {
    $PRIMARY_MEMBER_CONTACT = 14;
    $MEMBER_CONTACT = 15;
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
        civicrm_email e on e.contact_id = c.id and e.is_primary = 1
      where
        c.employer_id = %1
      and
        c.is_deleted = 0
      and
        exists (
          select
            rmc.id
          from
            civicrm_relationship rmc
          where
            rmc.contact_id_a = c.id
          and
            rmc.relationship_type_id in ($PRIMARY_MEMBER_CONTACT, $MEMBER_CONTACT)
          and
            rmc.is_active = 1
        )
        order by
          c.sort_name
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

    // get the website (we do it here, because there can be more than one)
    $sql = "
      SELECT
        *
      FROM
        civicrm_website
      WHERE
        contact_id = %1
    ";
    $sqlParams = [
      1 => [$row['contact_id'], 'Integer'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    $row['url'] = '';
    while ($dao->fetch()) {
      // fill in the first one, maybe it will be overwritten by one of type 'main'
      if ($row['url'] == '') {
        $row['url'] = $dao->url;
      }

      if ($dao->website_type_id == 6) {
        $row['url'] = $dao->url;
        break;
      }
    }
  }
}
