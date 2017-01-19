<?php

class CRM_Bemasreporting_Form_Report_BalancedScoreCard extends CRM_Report_Form {
  protected $_summary = NULL;
  const NUMYEARS = 5;

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'fields' => array(
          'column1' => array(
            'title' => '',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
        ),
      ),
    );

    // add years
    $currentYear = date('Y');
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $this->_columns['civicrm_contact']['fields'][$y] = array(
        'title' => $y,
        'required' => TRUE,
        'dbAlias' => '1',
      );
    }

    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Balanced Scorecard BEMAS vzw-asbl'));
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select);
  }

  function from() {
    $this->_from = "FROM  civicrm_contact {$this->_aliases['civicrm_contact']} ";
  }

  function where() {
      $this->_where = "WHERE id < 5 ";
  }

  function postProcess() {
    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function alterDisplay(&$rows) {
    $rows = array();
    $currentYear = date('Y');

    // TITLE ROW
    $row = array();
    $row['civicrm_contact_column1'] = '<strong>Network</strong>';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'New member contacts this period';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getNewMemberContactCount($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Terminated (included transfers)';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getTerminatedMemberContactCount($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Total number of member contacts';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getTotalMemberContactCount($y);
    }
    $rows[] = $row;

    // BLANK ROW
    $row = array();
    $row['civicrm_contact_column1'] = '';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'New member companies this period';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getNewMemberOrganizationsCount($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Number of member companies terminated';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getTerminatedMemberOrganizationsCount($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Total number of member companies';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getTotalMemberOrganizationsCount($y);
    }
    $rows[] = $row;

    // BLANK ROW
    $row = array();
    $row['civicrm_contact_column1'] = '';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // TITLE ROW
    $row = array();
    $row['civicrm_contact_column1'] = '<strong>Type of membership</strong>';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Effective members';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getEffectiveMembers($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Associated members';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getAssociatedMembers($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Admitted members';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getAdmittedMembers($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Individual members';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getIndividualMembers($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Membership [1-20]';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getMemberCountByID($y, 1);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Membership [21-50]';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getMemberCountByID($y, 2);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Membership [51-100]';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getMemberCountByID($y, 3);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Membership [101-500]';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getMemberCountByID($y, 4);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Membership [501-1000]';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getMemberCountByID($y, 5);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Membership [>1000]';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getMemberCountByID($y, 6);
    }
    $rows[] = $row;

    // BLANK ROW
    $row = array();
    $row['civicrm_contact_column1'] = '';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // TITLE ROW
    $row = array();
    $row['civicrm_contact_column1'] = '<strong>Languages</strong>';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Dutch speaking member companies';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getLanguageMemberOrganizations($y, 'NL');
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'French speaking member companies';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getLanguageMemberOrganizations($y, 'FR');
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Dutch speaking member contacts';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getLanguageMemberContacts($y, 'NL');
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'French speaking member contacts';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getLanguageMemberContacts($y, 'FR');
    }
    $rows[] = $row;

    // BLANK ROW
    $row = array();
    $row['civicrm_contact_column1'] = '';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // TITLE ROW
    $row = array();
    $row['civicrm_contact_column1'] = '<strong>Sector</strong>';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = '';
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Asset owners';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getAssetOwners($y);
    }
    $rows[] = $row;

    // NEW ROW
    $row = array();
    $row['civicrm_contact_column1'] = 'Non-asset owners';
    for ($i = self::NUMYEARS; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $this->getNonAssetOwners($y);
    }
    $rows[] = $row;
  }

  /**
   * @param $year
   * @return null|string
   */
  private function getNewMemberContactCount($year) {
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id 
      where
        m.join_date between '$year-01-01' and '$year-12-31'
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact')
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getTerminatedMemberContactCount($year) {
    $sql = "
      select
        count(*) * -1
      from
        civicrm_membership m
      inner JOIN 
        civicrm_membership_status s on m.status_id = s.id
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id 
      where
        m.end_date between '$year-01-01' and '$year-12-31'
        and s.name in ('Retired/Deceased', 'Terminated', 'Resigning', 'Bankrupt/Activity ceased', 'Cancelled')
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact', 'Mx - Ex-member contact')
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getTotalMemberContactCount($year) {
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id 
      where
        m.join_date between '$year-01-01' and '$year-12-31'
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact', 'Mx - Ex-member contact')
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getNewMemberOrganizationsCount($year) {
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      where
        m.join_date between '$year-01-01' and '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getTerminatedMemberOrganizationsCount($year) {
    $sql = "
      select
        count(*) * -1
      from
        civicrm_membership m
      inner JOIN 
        civicrm_membership_status s on m.status_id = s.id        
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      where
        m.end_date between '$year-01-01' and '$year-12-31'
        and m.owner_membership_id is null
        and s.name in ('Retired/Deceased', 'Terminated', 'Resigning', 'Bankrupt/Activity ceased', 'Cancelled')
        and c.is_deleted = 0      
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getTotalMemberOrganizationsCount($year) {
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_membership_status s on m.status_id = s.id        
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      where
        m.end_date between '$year-01-01' and '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0      
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getEffectiveMembers($year) {
    // 'Honorary' = 7, 'Individual membership' = 8, 'Free membership' = 10
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_membership_type mt on m.membership_type_id = mt.id
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      inner JOIN 
        civicrm_value_membership_15 mc on c.id = mc.entity_id
      where
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and mc.membership_type_58 = 2
        and m.membership_type_id not in (7, 8, 10)
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getAssociatedMembers($year) {
    // 'Honorary' = 7, 'Individual membership' = 8, 'Free membership' = 10
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      inner JOIN 
        civicrm_value_membership_15 mc on c.id = mc.entity_id
      where
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and mc.membership_type_58 = 1
        and m.membership_type_id not in (7, 8, 10)
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getAdmittedMembers($year) {
    // 'Honorary' = 7, 'Free membership' = 10
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      inner JOIN 
        civicrm_value_membership_15 mc on c.id = mc.entity_id
      where
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and mc.membership_type_58 in (1, 2)
        and m.membership_type_id in (7, 10)
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getIndividualMembers($year) {
    // 'Individual membership' = 8
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and m.membership_type_id = 8
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getMemberCountByID($year, $membershipTypeID) {
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and m.membership_type_id = $membershipTypeID
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getLanguageMemberOrganizations($year, $lang) {
    if ($lang == NL) {
      $langList = "'nl_NL', 'Dutch', 'Neder'";
    }
    else if ($lang == 'FR') {
      $langList = "'fr_FR', 'Frenc', 'Franc'";
    }
    else {
      return '';
    }

    // get the total number of members
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      where
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
    ";
    $total = CRM_Core_DAO::singleValueQuery($sql);

    // add language clause
    $sql .= " and c.preferred_language in ($langList)";
    $n = CRM_Core_DAO::singleValueQuery($sql);

    // calculate percentage
    $percentage = round($n / $total * 100, 2);

    return str_replace('.', ',', $percentage . '%');
  }

  private function getLanguageMemberContacts($year, $lang) {
    if ($lang == NL) {
      $langList = "'nl_NL', 'Dutch', 'Neder'";
    }
    else if ($lang == 'FR') {
      $langList = "'fr_FR', 'Frenc', 'Franc'";
    }
    else {
      return '';
    }

    // get the total number of member contacts
    $sql = "
      select
        count(*)
      from
        civicrm_membership m
      inner JOIN 
        civicrm_contact c on m.contact_id = c.id
      inner JOIN
        civicrm_value_individual_details_19 cd on c.id = cd.entity_id 
      where
        m.end_date between '$year-01-01' and '$year-12-31'
        and c.contact_type = 'Individual'
        and c.is_deleted = 0
        and cd.types_of_member_contact_60 in ('M1 - Primary member contact', 'Mc - Member contact', 'Mx - Ex-member contact')
        and c.preferred_language in ($langList)
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);

    return $n;
  }

  private function getAssetOwners($year) {
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
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and left(ac.type_of_activity__nace__6, 1) in ('A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'L', 'O', 'Q', 'R')
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getNonAssetOwners($year) {
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
        m.start_date <= '$year-12-31'        
        and m.end_date >= '$year-12-31'
        and m.owner_membership_id is null
        and c.is_deleted = 0
        and left(ac.type_of_activity__nace__6, 1) not in ('A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'L', 'O', 'Q', 'R')
    ";

    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

}
