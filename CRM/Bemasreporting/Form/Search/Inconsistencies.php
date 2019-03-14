<?php

class BemasInconsistenciesQuery {
  public $label;
  public $index;
  public $from;
  public $where;
}

class CRM_Bemasreporting_Form_Search_Inconsistencies extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  private $queries = [];
  private $queriesRadioButtons = [];

  function __construct(&$formValues) {
    $this->addQueries();

    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle('Fouten in de database');

    $form->addRadio('queryFilter', 'Te controleren:', $this->queriesRadioButtons, NULL, '<br>', TRUE);

    $form->assign('elements', ['queryFilter']);
  }

  function &columns() {
    // return by reference
    $columns = array(
      'Contact Id' => 'contact_id',
      'Naam' => 'sort_name',
    );
    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    return $sql;
  }

  function select() {
    $select = "
      contact_a.id as contact_id
      , contact_a.sort_name as sort_name
    ";

    return $select;
  }

  function from() {
    $values = $this->_formValues;
    if (array_key_exists('queryFilter', $values)) {
      $from = 'FROM ' . $this->queries[$values['queryFilter']]->from;
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
      $where = $this->queries[$values['queryFilter']]->where;
    }
    else {
      $where = '1=1';
    }

    return $this->whereClause($where, $whereParams);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function addQueries() {
    $index = 0;

    // contacten zonder prefix_id
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen zonder voorvoegsel (Dhr./Mevr.)';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id IS NULL
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
      $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // contacten met verkeerde prefix
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met een ander voorvoegsel dan Dhr./Mevr., M./Mme, Mr./Ms.';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id is not null
      and contact_a.prefix_id not in (11, 22)
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // verkeerde voorkeurstaal
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met onbekende voorkeurstaal';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      ifnull(preferred_language, '') not in ('en_US', 'nl_NL', 'fr_FR')
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // verkeerde weergavenaam (NL)
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen (NL) met weergavenaam zonder Dhr. of Mevr.';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      prefix_id in (11, 22)
      and display_name not like 'Dhr. %'
      and display_name not like 'Mevr. %'
      and preferred_language = 'nl_NL'
      and contact_type = 'Individual'
      and is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // verkeerde weergavenaam (FR)
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen (FR) met weergavenaam zonder M. of Mme';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      prefix_id in (11, 22)
      and display_name not like 'M. %'
      and display_name not like 'Mme %'
      and preferred_language = 'fr_FR'
      and contact_type = 'Individual'
      and is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // verkeerde weergavenaam (EN)
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen (EN) met weergavenaam zonder Mr. of Ms.';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      prefix_id in (11, 22)
      and display_name not like 'Mr. %'
      and display_name not like 'Ms. %'
      and preferred_language = 'en_US'
      and contact_type = 'Individual'
      and is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;
  }
}
