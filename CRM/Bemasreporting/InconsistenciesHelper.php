<?php


class BemasInconsistenciesQuery {
  public $label;
  public $index;
  public $from;
  public $where;
}

class CRM_Bemasreporting_InconsistenciesHelper {
  public $queries = [];
  public $queriesRadioButtons = [];

  public function __construct() {
    $this->addQueries();
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

    // we hebben een job title, maar geen functiecode
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met een functie, maar geen functiecode';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      left outer join civicrm_value_individual_details_19 id on id.entity_id = contact_a.id
    ";
    $q->where = "
      ifnull(contact_a.job_title, '') <> ''
      and ifnull(id.function_28, '') = ''
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // we hebben een functiecode, maar geen e-mail
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met een functiecode, maar geen e-mailadres';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      left outer join civicrm_value_individual_details_19 id on id.entity_id = contact_a.id
      left outer join civicrm_email e on e.contact_id = contact_a.id and e.is_primary = 1
    ";
    $q->where = "
      ifnull(id.function_28, '') <> ''
      and e.id is null
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // namen zonder hoofdletters
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Voornaam en/of achternaam zonder hoofdletters';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      (first_name COLLATE utf8_bin = LOWER(first_name) COLLATE utf8_bin
      or last_name COLLATE utf8_bin = LOWER(last_name) COLLATE utf8_bin) 
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;
  }
}