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
    $this->selectColumns = 'count(*)';
  }

  public function setReturnModeAllRecords() {
    $this->countMode = FALSE;
    $this->orderBy = ' order by c.sort_name ';
    $this->selectColumns = ' c.id balanced_score_card_id, c.display_name balanced_score_card_display_name ';
  }


  public function getNewMemberContactCount($year) { return 9229; }
  public function getTerminatedMemberContactCount($year) { return 99; }
  public function getTotalMemberContactCount($year) { return 99; }

  public function getNewMemberOrganizationsCount($year) { return 99; }
  public function getTerminatedMemberOrganizationsCount($year) { return 99; }
  public function getTotalMemberOrganizationsCount($year) { return 99; }
  public function getIndividualMembers($year) { return 99; }


  public function getEffectiveMembers($year) { return 99; }
  public function getAssociatedMembers($year) { return 99; }


  public function getFreeMembers($year) { return 99; }  // getMemberCountByID($y, 10)
  public function getHonoraryMembers($year) { return 99; }  // getMemberCountByID($y, 7)

  public function getAcademicMembers($year) { return 99; }  // getMemberCountByID($y, 9)
  public function getMembers1to20($year) { return 99; }  // getMemberCountByID($y, 1)
  public function getMembers21to50($year) { return 99; }  // getMemberCountByID($y, 2)
  public function getMembers51to100($year) { return 99; }  // getMemberCountByID($y, 3)
  public function getMembers101to500($year) { return 99; }  // getMemberCountByID($y, 4)
  public function getMembers501to1000($year) { return 99; }  // getMemberCountByID($y, 5)
  public function getMembers1001andMore($year) { return 99; }  // getMemberCountByID($y, 6)


  public function getDutchMemberOrganizations($year) { return 99; }  // getLanguageMemberOrganizations($y, 'NL')
  public function getFrenchMemberOrganizations($year) { return 99; }  // getLanguageMemberOrganizations($y, 'FR')
  public function getNonDutchAndFrenchMemberOrganizations($year) { return 99; }  // getLanguageMemberOrganizations($y, 'other')
  public function getDutchMemberContacts($year) { return 7; }  // getLanguageMemberContacts($y, 'NL')
  public function getFrenchMemberContacts($year) { return 99; }  // getLanguageMemberContacts($y, 'FR')

  public function getNonDutchAndFrenchMemberContacts($year) {
    return 101; // getLanguageMemberContacts($y, 'other')
    $n = $this->getCountOrSql($sql);
    return $n;
  }

  public function getAssetOwners($year) {
    $sql = "
      select
        count(*)
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
