<?php
use CRM_Bemasreporting_ExtensionUtil as E;

class CRM_Bemasreporting_Form_Search_BemasMagazine extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  private $INDIVIDUAL_PREFIX_GROUP_ID = 6;
  private $MAGAZINE_ADDRESS_TYPE_ID = 7;

  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle(E::ts('BEMAS Magazine adressen'));
  }

  function &columns() {
    // return by reference
    $columns = [
      'Contact Id' => 'contact_id',
      'Organization' => 'organization_name',
      'Language' => 'preferred_language',
      'Addresse' => 'addressee_display',
      'First Name' => 'first_name',
      'Last Name' => 'last_name',
      'Street' => 'street_address',
      'Extra address line 1' => 'supplemental_address_1',
      'Extra address line 2' => 'supplemental_address_2',
      'Postal Code' => 'postal_code',
      'City' => 'city',
      'Country' => 'country',
    ];
    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    //die($sql);
    return $sql;
  }

  function select() {
    $values = $this->_formValues;

    $select = "
      contact_a.id as contact_id
      , contact_a.id
      , contact_a.organization_name
      , contact_a.preferred_language
      , contact_a.addressee_display
      , contact_a.first_name
      , contact_a.last_name
      , if(magpref.versturen_naar_149 = 2, mag_a.street_address, empl_a.street_address) street_address
      , if(magpref.versturen_naar_149 = 2, mag_a.supplemental_address_1, empl_a.supplemental_address_1) supplemental_address_1
      , if(magpref.versturen_naar_149 = 2, mag_a.supplemental_address_2, empl_a.supplemental_address_2) supplemental_address_2
      , if(magpref.versturen_naar_149 = 2, mag_a.postal_code, empl_a.postal_code) postal_code
      , if(magpref.versturen_naar_149 = 2, mag_a.city, empl_a.city) city
      , if(magpref.versturen_naar_149 = 2, mag_a_ctry.name, empl_a_ctry.name) country
    ";

    return $select;
  }

  function from() {
    $from = "
      FROM
        civicrm_contact contact_a
      INNER JOIN
        civicrm_membership m on contact_a.id = m.contact_id and m.membership_type_id between 1 and 10
      LEFT OUTER JOIN
        civicrm_value_magazine_41 magpref on magpref.entity_id = contact_a.id
      LEFT OUTER JOIN
        civicrm_address mag_a on mag_a.contact_id = contact_a.id and mag_a.location_type_id = 7
      LEFT OUTER JOIN
        civicrm_country mag_a_ctry on mag_a_ctry.id = mag_a.country_id
      LEFT OUTER JOIN
        civicrm_address empl_a on empl_a.contact_id = contact_a.employer_id and empl_a.is_primary = 1
      LEFT OUTER JOIN
        civicrm_country empl_a_ctry on empl_a_ctry.id = empl_a.country_id
    ";

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    $where = "
        contact_a.is_deleted = 0
      and
        contact_a.is_deceased = 0
      and
        contact_a.contact_type = 'Individual'
      and
        m.start_date <= NOW() and m.end_date >= NOW()
      and
        ifnull(magpref.versturen_naar_149, 1) in (1, 2)
    ";

    $params = [];
    return $this->whereClause($where, $params);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }
}
