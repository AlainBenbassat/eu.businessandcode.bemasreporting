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
      , contact_a.sort_name as sort_name
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
      $from = 'FROM ' . $this->queries[$values['queryFilter']]->from
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
      ifnull(contact_a.preferred_language, '') not in ('en_US', 'nl_NL', 'fr_FR')
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
      contact_a.prefix_id in (11, 22)
      and contact_a.display_name not like 'Dhr. %'
      and contact_a.display_name not like 'Mevr. %'
      and contact_a.preferred_language = 'nl_NL'
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
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
      contact_a.prefix_id in (11, 22)
      and contact_a.display_name not like 'M. %'
      and contact_a.display_name not like 'Mme %'
      and contact_a.preferred_language = 'fr_FR'
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
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
      contact_a.prefix_id in (11, 22)
      and contact_a.display_name not like 'Mr. %'
      and contact_a.display_name not like 'Ms. %'
      and contact_a.preferred_language = 'en_US'
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // werkgever maar geen relatie
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Werkgever maar geen relatie "medewerker van"';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.employer_id > 0
      and not exists (
        select * from civicrm_relationship r where r.contact_id_a = contact_a.id and r.relationship_type_id = 4 and r.is_active = 1
      )
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // werkgeversrelatie maar geen werkgever
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Actieve "medewerker van"-relatie maar geen werkgever';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      ifnull(contact_a.employer_id, 0) = 0
      and exists (
        select * from civicrm_relationship r where r.contact_id_a = contact_a.id and r.relationship_type_id = 4 and r.is_active = 1
      )
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // aanspreking = Mevr, geslacht = M
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Aanspreking is vrouwelijk, geslacht is mannelijk';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id = 11
      and contact_a.gender_id = 2
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // aanspreking = Dhr, geslacht = F
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Aanspreking is mannelijk, geslacht is vrouwelijk';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id = 22
      and contact_a.gender_id = 1
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // vrouwen zonder geslacht
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Vrouwen zonder geslacht';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id = 11
      and ifnull(contact_a.gender_id, 0) = 0
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;
  }
}
