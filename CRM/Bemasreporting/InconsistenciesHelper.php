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
    $PRIMARY_MEMBER_CONTACT = 14;
    $MEMBER_CONTACT = 15;

    $index = 0;

    // namen zonder hoofdletters
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

    // werkgever is lid, maar persoon heeft geen lidmaatschap
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Werkgever is lid, maar persoon heeft geen lidmaatschap';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      inner join
        civicrm_relationship r on contact_a.id = r.contact_id_a and r.contact_id_b = contact_a.employer_id
      inner join
        civicrm_membership memp on contact_a.employer_id = memp.contact_id
      left outer join
        civicrm_membership mpers on contact_a.id = mpers.contact_id
    ";
    $q->where = "
      contact_a.contact_type = 'Individual'
      and mpers.id is null
      and memp.start_date <= NOW() and memp.end_date >= NOW()
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // Persoon heeft M1 of MC, maar geen lidmaatschap
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Persoon is (Primary) Member Contact, maar heeft geen lidmaatschap';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
    ";
    $q->where = "
      contact_a.contact_type = 'Individual'
      and exists (
        select
          rmc.id
        from
          civicrm_relationship rmc
        where
          rmc.contact_id_a = contact_a.id
        and
          rmc.relationship_type_id in ($PRIMARY_MEMBER_CONTACT, $MEMBER_CONTACT)
        and
          rmc.is_active = 1
      )
      and not exists (
        select
          m.id
        from
          civicrm_membership m
        where
          m.contact_id = contact_a.id
        and
          m.membership_type_id between 1 and 10
        and
          m.start_date <= NOW()
        and
          m.end_date >= NOW()
      )
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

    // contacten zonder prefix_id
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

    // verkeerde weergavenaam (NL)
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

    // verkeerde weergavenaam (FR)
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

    // verkeerde weergavenaam (EN)
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

    // Vrouwelijke aanspreking, maar geen geslacht
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

    // Mannelijke aanspreking, maar geen geslacht
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

    // contacten met verkeerde prefix
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

    // land is België, maar postcode <> 4
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

    // postcode zonder cijfers
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

    // Nederlandstalig persoon, maar bedrijf gevestigd in Wallonië OF Frankrijk OF Luxemburg
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

    // Franstalige persoon, maar bedrijf gevestigd in Vlaanderen OF Nederland
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

    // personen zonder familienaam
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

    // we hebben een functiecode + e-mail, maar geen werkgever
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

    // we hebben een functiecode, maar geen e-mail
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

    // personen met 2 lidmaatschappen
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met meerdere lidmaatschappen';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a";
    $q->where = "
      contact_a.is_deleted = 0
      and contact_a.contact_type = 'Individual'
      and (select count(m.id) from civicrm_membership m where m.contact_id = contact_a.id and m.status_id = 2) > 1
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // personen met meerdere event rollen (is momenteel een probleem, zie https://lab.civicrm.org/dev/core/-/issues/2377
    $q = new BemasInconsistenciesQuery();
    $q->label = 'Personen met meerdere deelnemersrollen (BLOKKEERT HERINNERINGSMAILS)';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
    ";
    $q->where = "
      contact_a.is_deleted = 0
      and contact_a.contact_type = 'Individual'
      and exists (
        select
          p.id
        from
          civicrm_participant p
        where
          p.contact_id = contact_a.id
        and
          length(role_id) > 1
        and
          p.register_date > '2021-01-01'
      )
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;
  }
}
