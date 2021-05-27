<?php

class CRM_Bemasreporting_BalancedScoreCardHelper {
  public $rowHeaders = [];

  public function __construct() {
    $this->fillRowHeaders();
  }

  private function fillRowHeaders() {
    // the BSC consists of 5 sections
    $this->fillRowHeadersSectionNetwork();
    $this->fillRowHeadersSectionTypeOfMembership();
    $this->fillRowHeadersSectionCategoryOfMembership();
    $this->fillRowHeadersSectionLanguages();
    $this->fillRowHeadersSectionSector();
  }

  private function fillRowHeadersSectionNetwork() {
    $rowCounter = count($this->rowHeaders);
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Network',
      'is_header' => TRUE,
      'method' => '',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'New member contacts this period',
      'is_header' => FALSE,
      'method' => 'getNewMemberContactCount',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Terminated member contacts (incl. transfers)',
      'is_header' => FALSE,
      'method' => 'getTerminatedMemberContactCount',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Total number of member contacts',
      'is_header' => FALSE,
      'method' => 'getTotalMemberContactCount',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => '',
      'is_header' => TRUE,
      'method' => '',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'New member companies this period',
      'is_header' => FALSE,
      'method' => 'getNewMemberOrganizationsCount',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Number of member companies terminated',
      'is_header' => FALSE,
      'method' => 'getTerminatedMemberOrganizationsCount',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Total number of member companies',
      'is_header' => FALSE,
      'method' => 'getTotalMemberOrganizationsCount',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Individual members',
      'is_header' => FALSE,
      'method' => 'getIndividualMembers',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => '',
      'is_header' => TRUE,
      'method' => '',
    ];
  }

  private function fillRowHeadersSectionTypeOfMembership() {
    $rowCounter = count($this->rowHeaders);
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Type of membership',
      'is_header' => TRUE,
      'method' => '',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Effective members',
      'is_header' => FALSE,
      'method' => 'getEffectiveMembers',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Associated members',
      'is_header' => FALSE,
      'method' => 'getAssociatedMembers',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => '',
      'is_header' => TRUE,
      'method' => '',
    ];
  }

  private function fillRowHeadersSectionCategoryOfMembership() {
    $rowCounter = count($this->rowHeaders);
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Category of membership',
      'is_header' => TRUE,
      'method' => '',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Free members',
      'is_header' => FALSE,
      'method' => 'getFreeMembers', // getMemberCountByID($y, 10)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Honorary members',
      'is_header' => FALSE,
      'method' => 'getHonoraryMembers', // getMemberCountByID($y, 7)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Individual members',
      'is_header' => FALSE,
      'method' => 'getIndividualMembers',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Academic members',
      'is_header' => FALSE,
      'method' => 'getAcademicMembers', // getMemberCountByID($y, 9)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Membership [1-20]',
      'is_header' => FALSE,
      'method' => 'getMembers1to20', // getMemberCountByID($y, 1)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Membership [21-50]',
      'is_header' => FALSE,
      'method' => 'getMembers21to50', // getMemberCountByID($y, 2)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Membership [51-100]',
      'is_header' => FALSE,
      'method' => 'getMembers51to100', // getMemberCountByID($y, 3)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Membership [101-500]',
      'is_header' => FALSE,
      'method' => 'getMembers101to500', // getMemberCountByID($y, 4)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Membership [501-1000]',
      'is_header' => FALSE,
      'method' => 'getMembers501to1000', // getMemberCountByID($y, 5)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Membership [>1000]',
      'is_header' => FALSE,
      'method' => 'getMembers1001andMore', // getMemberCountByID($y, 6)
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => '',
      'is_header' => TRUE,
      'method' => '',
    ];
  }

  private function fillRowHeadersSectionLanguages() {
    $rowCounter = count($this->rowHeaders);
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Languages',
      'is_header' => TRUE,
      'method' => '',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Dutch speaking member companies',
      'is_header' => FALSE,
      'method' => 'getDutchMemberOrganizations', // getLanguageMemberOrganizations($y, 'NL')
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'French speaking member companies',
      'is_header' => FALSE,
      'method' => 'getFrenchMemberOrganizations', // getLanguageMemberOrganizations($y, 'FR')
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Other languages',
      'is_header' => FALSE,
      'method' => 'getNonDutchAndFrenchMemberOrganizations', // getLanguageMemberOrganizations($y, 'other')
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Dutch speaking member contacts',
      'is_header' => FALSE,
      'method' => 'getDutchMemberContacts', // getLanguageMemberContacts($y, 'NL')
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'French speaking member contacts',
      'is_header' => FALSE,
      'method' => 'getFrenchMemberContacts', // getLanguageMemberContacts($y, 'FR')
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Other languages member contacts',
      'is_header' => FALSE,
      'method' => 'getNonDutchAndFrenchMemberContacts', // getLanguageMemberContacts($y, 'other')
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => '',
      'is_header' => TRUE,
      'method' => '',
    ];
  }

  private function fillRowHeadersSectionSector() {
    $rowCounter = count($this->rowHeaders);
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Sector',
      'is_header' => TRUE,
      'method' => '',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Asset owners',
      'is_header' => FALSE,
      'method' => 'getAssetOwners',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => 'Non-asset owners',
      'is_header' => FALSE,
      'method' => 'getNonAssetOwners',
    ];
    $this->rowHeaders['Q' . ++$rowCounter] = [
      'label' => '',
      'is_header' => TRUE,
      'method' => '',
    ];
  }
}
