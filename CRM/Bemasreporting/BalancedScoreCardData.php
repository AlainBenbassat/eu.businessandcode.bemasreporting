<?php

class CRM_Bemasreporting_BalancedScoreCardData {
  private $countMode;
  private $orderBy;
  private $selectColumns;

  public function __construct() {
    $this->setReturnModeCount();
  }

  public function setReturnModeCount() {
    $this->countMode = TRUE;
    $this->orderBy = '';
    $this->selectColumns = 'count(distinct c.id)';
  }

  public function setReturnModeAllRecords() {
    $this->countMode = FALSE;
    $this->orderBy = ' order by c.sort_name ';
    $this->selectColumns = ' c.id balanced_score_card_id, c.display_name balanced_score_card_display_name ';
  }

  public function getNewMemberContactCount($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id
      where
        m.start_date between '$year-01-01' and '$year-12-30'
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact')
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getTerminatedMemberContactCount($year) {
    $previousYear = $year - 1;
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_membership_status s on m.status_id = s.id
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id
      where
        m.end_date between '$previousYear-12-31' and '$year-12-30'
        and s.name in ('Retired/Deceased', 'Terminated', 'Bankrupt/Activity ceased', 'Cancelled', 'Expired')
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('Mx - Ex-member contact')
    ";

    $n = $this->getCountOrSql($sql);

    if ($this->countMode) {
      // in count mode, we make the number negative
      // that's better on the BSC
      $n = $n * -1;
    }

    return $n;
  }

  public function getTotalMemberContactCount($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact')
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getNewMemberOrganizationsCount($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date between '$year-01-01' and '$year-12-30'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and c.contact_type = 'Organization'
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getTerminatedMemberOrganizationsCount($year) {
    $previousYear = $year - 1;
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_membership_status s on m.status_id = s.id
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      where
        m.end_date between '$previousYear-12-31' and '$year-12-30'
        and m.owner_membership_id is null
        and s.name in ('Retired/Deceased', 'Terminated', 'Resigning', 'Bankrupt/Activity ceased', 'Cancelled', 'Expired')
        and c.is_deleted = 0
        and c.contact_type = 'Organization'
    ";

    $n = $this->getCountOrSql($sql);

    if ($this->countMode) {
      // in count mode, we make the number negative
      // that's better on the BSC
      $n = $n * -1;
    }

    return $n;
  }

  public function getTotalMemberOrganizationsCount($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_membership_status s on m.status_id = s.id
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and c.contact_type = 'Organization'
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getIndividualMembers($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and c.contact_type = 'Individual'
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getEffectiveMembers($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_membership_type mt on m.membership_type_id = mt.id
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_membership_15 mc on c.id = mc.entity_id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and mc.membership_type_58 = 2
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getAssociatedMembers($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_membership_type mt on m.membership_type_id = mt.id
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_membership_15 mc on c.id = mc.entity_id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and mc.membership_type_58 = 1
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getFreeMembers($year) {
    return $this->getMemberCountByID($year, 10);
  }

  public function getHonoraryMembers($year) {
    return $this->getMemberCountByID($year, 7);
  }

  public function getAcademicMembers($year) {
    return $this->getMemberCountByID($year, 9);
  }

  public function getMembers1to20($year) {
    return $this->getMemberCountByID($year, 1);
  }

  public function getMembers21to50($year) {
    return $this->getMemberCountByID($year, 2);
  }

  public function getMembers51to100($year) {
    return $this->getMemberCountByID($year, 3);
  }

  public function getMembers101to500($year) {
    return $this->getMemberCountByID($year, 4);
  }

  public function getMembers501to1000($year) {
    return $this->getMemberCountByID($year, 5);
  }

  public function getMembers1001andMore($year) {
    return $this->getMemberCountByID($year, 6);
  }

  private function getMemberCountByID($year, $membershipTypeID) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and m.membership_type_id = $membershipTypeID
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getDutchMemberOrganizations($year) {
    return $this->getLanguageMemberOrganizations($year, 'NL');
  }

  public function getFrenchMemberOrganizations($year) {
    return $this->getLanguageMemberOrganizations($year, 'FR');
  }

  public function getNonDutchAndFrenchMemberOrganizations($year) {
    return $this->getLanguageMemberOrganizations($year, 'other');
  }

  public function getDutchMemberContacts($year) {
    return $this->getLanguageMemberContacts($year, 'NL');
  }

  public function getFrenchMemberContacts($year) {
    return $this->getLanguageMemberContacts($year, 'FR');
  }

  public function getNonDutchAndFrenchMemberContacts($year) {
    return $this->getLanguageMemberContacts($year, 'other');
  }

  private function getLanguageMemberOrganizations($year, $lang) {
    if ($lang == 'NL') {
      $langList = "in ('nl_NL', 'Dutch', 'Neder')";
    }
    else if ($lang == 'FR') {
      $langList = "in ('fr_FR', 'Frenc', 'Franc')";
    }
    else if ($lang == 'other') {
      $langList = "not in ('nl_NL', 'Dutch', 'Neder', 'fr_FR', 'Frenc', 'Franc')";
    }
    else {
      return '';
    }

    // get the total number of members
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_membership_status s on m.status_id = s.id
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and c.contact_type = 'Organization'
    ";

    // add language clause
    $sql .= " and c.preferred_language $langList";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  private function getLanguageMemberContacts($year, $lang) {
    if ($lang == 'NL') {
      $langList = "in ('nl_NL', 'Dutch', 'Neder')";
    }
    else if ($lang == 'FR') {
      $langList = "in ('fr_FR', 'Frenc', 'Franc')";
    }
    else if ($lang == 'other') {
      $langList = "not in ('nl_NL', 'Dutch', 'Neder', 'fr_FR', 'Frenc', 'Franc')";
    }
    else {
      return '';
    }

    // get the total number of member contacts
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact')
    ";

    // add language clause
    $sql .= " and c.preferred_language $langList";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getAssetOwners($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_activity_9 ac on c.id = ac.entity_id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and left(ac.type_of_activity__nace__6, 1) in ('A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'L', 'O', 'Q', 'R')
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getNonAssetOwners($year) {
    $sql = "
      select
        {$this->selectColumns}
      from
        civicrm_membership m
      inner JOIN
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_activity_9 ac on c.id = ac.entity_id
      where
        m.start_date <= '$year-12-30'
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and left(ac.type_of_activity__nace__6, 1) not in ('A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'L', 'O', 'Q', 'R')
      {$this->orderBy}
    ";

    $n = $this->getCountOrSql($sql);
    return $n;
  }

  private function getCountOrSql($sql) {
    if ($this->countMode) {
      return CRM_Core_DAO::singleValueQuery($sql);
    }
    else {
      return $sql;
    }
  }
}
