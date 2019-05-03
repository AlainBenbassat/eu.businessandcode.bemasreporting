<?php

/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/



class CRM_Bemasreporting_Form_Search_Inconsistencies extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  private $helper;

  function __construct(&$formValues) {
    $this->helper = new CRM_Bemasreporting_InconsistenciesHelper();

    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle('Fouten in de database');

    $form->addRadio('queryFilter', 'Te controleren:', $this->helper->queriesRadioButtons, NULL, '<br>', TRUE);

    $form->assign('elements', ['queryFilter']);
  }

  function &columns() {
    // return by reference
    $columns = array(
      'Contact Id' => 'contact_id',
      'contact' => 'sort_name',
      'Geslacht' => 'gender',
      'Weergavenaam' => 'display_name',
      'Taal' => 'preferred_language',
      'Voornaam' => 'first_name',
      'Achternaam' => 'last_name',
      'Functie' => 'job_title',
      'Organisatie' => 'organization_name',
      'Postcode' => 'postal_code',
      'Gemeente' => 'city',
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
      contact_a.id as contact_id
      , contact_a.sort_name
      , if (contact_a.gender_id=1,'Man',if(contact_a.gender_id=2,'Vrouw', 'Onbekend')) as gender
      , contact_a.preferred_language
      , contact_a.display_name as display_name
      , contact_a.first_name
      , contact_a.last_name
      , contact_a.job_title
      , org.organization_name
      , orgaddr.postal_code
      , orgaddr.city
    ";

    return $select;
  }

  function from() {
    $values = $this->_formValues;
    if (array_key_exists('queryFilter', $values)) {
      $from = 'FROM ' . $this->helper->queries[$values['queryFilter']]->from
        . ' left outer join civicrm_contact org on org.id = contact_a.employer_id'
        . ' left outer join civicrm_address orgaddr on orgaddr.contact_id = org.id and orgaddr.is_primary = 1';
    }
    else {
      $from = "FROM civicrm_contact contact_a";
    }

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    $whereParams = [];

    $values = $this->_formValues;
    if (array_key_exists('queryFilter', $values)) {
      $where = $this->helper->queries[$values['queryFilter']]->where;
    }
    else {
      $where = '1=1';
    }

    return $this->whereClause($where, $whereParams);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }
}
