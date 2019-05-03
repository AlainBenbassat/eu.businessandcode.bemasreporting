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

    // 1. namen zonder hoofdletters
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Voornaam en/of achternaam zonder hoofdletters';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.last_name <> '.'
      and contact_a.last_name <> ''
      and contact_a.first_name <> '.'
      and contact_a.first_name <> ''
      and (
        contact_a.first_name COLLATE utf8_bin = LOWER(contact_a.first_name) COLLATE utf8_bin
      or 
        contact_a.last_name COLLATE utf8_bin = LOWER(contact_a.last_name) COLLATE utf8_bin
      ) 
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 2. we hebben een job title, maar geen functiecode
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

    // 3. verkeerde voorkeurstaal
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

    // 4. werkgeversrelatie maar geen werkgever
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

    // 5. werkgever maar geen relatie
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

    // 6. contacten zonder prefix_id
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen zonder voorvoegsel (Dhr./Mevr.)';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id IS NULL
      and ifnull(contact_a.last_name, '') <> ''       
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 7. verkeerde weergavenaam (NL)
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen (NL) met weergavenaam zonder Dhr. of Mevr.';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id in (11, 22)
      and ifnull(contact_a.last_name, '') <> ''
      and contact_a.display_name not like 'Dhr. %'
      and contact_a.display_name not like 'Mevr. %'
      and contact_a.preferred_language = 'nl_NL'
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 8. verkeerde weergavenaam (FR)
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen (FR) met weergavenaam zonder M. of Mme';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id in (11, 22)
      and ifnull(contact_a.last_name, '') <> ''
      and contact_a.display_name not like 'M. %'
      and contact_a.display_name not like 'Mme %'
      and contact_a.preferred_language = 'fr_FR'
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 9. verkeerde weergavenaam (EN)
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen (EN) met weergavenaam zonder Mr. of Ms.';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id in (11, 22)
      and ifnull(contact_a.last_name, '') <> ''
      and contact_a.display_name not like 'Mr. %'
      and contact_a.display_name not like 'Ms. %'
      and contact_a.preferred_language = 'en_US'
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 10. Vrouwelijke aanspreking, maar geen geslacht
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Vrouwelijke aanspreking, maar geen geslacht';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id = 11
      and contact_a.last_name <> '.'
      and contact_a.last_name <> ''
      and contact_a.first_name <> '.'
      and contact_a.first_name <> ''
      and ifnull(contact_a.gender_id, 0) = 0
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 11. Mannelijke aanspreking, maar geen geslacht
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Mannelijke aanspreking, maar geen geslacht';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id = 22
      and contact_a.last_name <> '.'
      and contact_a.last_name <> ''
      and contact_a.first_name <> '.'
      and contact_a.first_name <> ''
      and ifnull(contact_a.gender_id, 0) = 0
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 12. aanspreking = Mevr, geslacht = M
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

    // 13. aanspreking = Dhr, geslacht = F
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

    // 14. contacten met verkeerde prefix
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met een ander voorvoegsel dan Dhr./Mevr., M./Mme, Mr./Ms.';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.prefix_id is not null
      and ifnull(contact_a.last_name, '') <> ''
      and contact_a.prefix_id not in (11, 22)
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 15. land is België, maar postcode <> 4
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Land is België, maar postcode <> 4 cijfers';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a inner join civicrm_address a on contact_a.id = a.contact_id";
    $q->where = "
      a.postal_code <> '' and length(a.postal_code) <> 4
      and a.country_id = 1020 
      and a.master_id IS NULL
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 16. postcode zonder cijfers
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Postcodes zonder cijfers';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      exists (
        select a.id from civicrm_address a
        where 
          a.postal_code REGEXP '^[a-zA-Z \-]+$'
        and 
          a.master_id is NULL
        and
          a.contact_id = contact_a.id
      ) 
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 17. Nederlandstalig persoon, maar bedrijf gevestigd in Wallonië OF Frankrijk OF Luxemburg
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Nederlandstalig persoon, maar bedrijf gevestigd in Wallonië OF Frankrijk OF Luxemburg';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.preferred_language = 'nl_NL'
      and exists (
        select a.id from civicrm_address a
        where
          a.contact_id = contact_a.employer_id
        and 
          a.master_id is NULL
        and 
        (
          (  
            a.country_id = 1020
          and 
            a.postal_code between '4000' and '7999'
          )
          or
            a.country_id = 1026
          or
            a.country_id = 1076
        )              
      )
      and contact_a.contact_type = 'Individual' 
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 18. Franstalige persoon, maar bedrijf gevestigd in Vlaanderen OF Nederland
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Franstalige persoon, maar bedrijf gevestigd in Vlaanderen OF Nederland ';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.preferred_language = 'fr_FR'
      and exists (
        select a.id from civicrm_address a
        where
          a.contact_id = contact_a.employer_id
        and 
          a.master_id is NULL
        and 
        (
          (  
            a.country_id = 1020
          and 
            a.postal_code not between '4000' and '7999'
          and 
            a.postal_code not between '1000' and '1999'            
          )
          or
            a.country_id = 1152
        )              
      )
      and contact_a.contact_type = 'Individual' 
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 19. personen zonder familienaam
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen zonder familienaam';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      ifnull(contact_a.last_name, '') = ''       
      and contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 20. we hebben een functiecode + e-mail, maar geen werkgever
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met een functiecode en e-mail, maar zonder werkgever<br>(en geen e-mail opt-out)';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      inner join civicrm_email e on e.contact_id = contact_a.id and e.is_primary = 1
      left outer join civicrm_value_individual_details_19 id on id.entity_id = contact_a.id      
    ";
    $q->where = "
      ifnull(id.function_28, '') <> ''
      and contact_a.contact_type = 'Individual'
      and ifnull(contact_a.employer_id, 0) = 0
      and contact_a.is_deleted = 0
      and ifnull(id.function_28, '') not like 'RET%'
      and e.on_hold = 0
      and contact_a.is_opt_out = 0
      and contact_a.do_not_mail = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // 21. we hebben een functiecode, maar geen e-mail
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met een functiecode, maar geen e-mailadres<br>(exclusief gepensioneerd en onbekende werkgever)';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      left outer join civicrm_value_individual_details_19 id on id.entity_id = contact_a.id
      left outer join civicrm_email e on e.contact_id = contact_a.id and e.is_primary = 1
    ";
    $q->where = "
      ifnull(id.function_28, '') <> ''
      and e.id is null
      and contact_a.contact_type = 'Individual'
      and contact_a.organization_name <> 'Retired - gepensioneerd - pensionné'
      and contact_a.organization_name <> 'Unknown - Onbekend - Inconnu'
      and contact_a.employer_id IS NOT NULL
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;
  }
}