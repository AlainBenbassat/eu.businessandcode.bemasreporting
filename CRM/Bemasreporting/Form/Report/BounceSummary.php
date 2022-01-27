<?php

class CRM_Bemasreporting_Form_Report_BounceSummary extends CRM_Report_Form {
  protected $_summary = NULL;

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'fields' => array(
          'types_of_member_contact_60' => array(
            'title' => 'Type contact',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
          'lang_nl' => array(
            'title' => 'NL',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
          'lang_fr' => array(
            'title' => 'FR',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
          'lang_total' => array(
            'title' => 'Totaal',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
        ),
      ),
    );

    parent::__construct();
  }

  public function preProcess() {
    $this->assign('reportTitle', ts('Bounces Summary'));
    parent::preProcess();
  }

  public function select() {
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

  public function from() {
    $this->_from = "FROM  civicrm_contact {$this->_aliases['civicrm_contact']} ";
  }

  public function where() {
    $this->_where = "WHERE id < 2  ";
  }

  public function limit($rowCount = self::ROW_COUNT_LIMIT) {
    return parent::limit(10);
  }

  public function postProcess() {
    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function alterDisplay(&$rows) {
    $rows[0]['civicrm_contact_types_of_member_contact_60'] = 'M1';
    $rows[0]['civicrm_contact_lang_nl'] = $this->getNumBounces("m1", '', 'nl');
    $rows[0]['civicrm_contact_lang_fr'] = $this->getNumBounces("m1", '', 'fr');
    $rows[0]['civicrm_contact_lang_total'] = $this->getNumBounces("m1", '', '');

    $rows[1]['civicrm_contact_types_of_member_contact_60'] = 'Mc';
    $rows[1]['civicrm_contact_lang_nl'] = $this->getNumBounces("mc", '', 'nl');
    $rows[1]['civicrm_contact_lang_fr'] = $this->getNumBounces("mc", '', 'fr');
    $rows[1]['civicrm_contact_lang_total'] = $this->getNumBounces("mc", '', '');

    $rows[2]['civicrm_contact_types_of_member_contact_60'] = 'Mx';
    $rows[2]['civicrm_contact_lang_nl'] = $this->getNumBounces("mx", '', 'nl');
    $rows[2]['civicrm_contact_lang_fr'] = $this->getNumBounces("mx", '', 'fr');
    $rows[2]['civicrm_contact_lang_total'] = $this->getNumBounces("mx", '', '');

    $funcArr = array('MNGR', 'TECH', 'ENG', 'ASSET', 'FSM', 'DIRPROD');
    for ($i = 0; $i < count($funcArr); $i++) {
      $rows[3 + $i]['civicrm_contact_types_of_member_contact_60'] = $funcArr[$i];
      $rows[3 + $i]['civicrm_contact_lang_nl'] = $this->getNumBounces('', $funcArr[$i], 'nl');
      $rows[3 + $i]['civicrm_contact_lang_fr'] = $this->getNumBounces('', $funcArr[$i], 'fr');
      $rows[3 + $i]['civicrm_contact_lang_total'] = $this->getNumBounces('', $funcArr[$i], '');
    }

    $i = $i + 3;
    $rows[$i]['civicrm_contact_types_of_member_contact_60'] = '<strong>Totaal</strong>';
    $rows[$i]['civicrm_contact_lang_nl'] = $this->getNumBounces('', '', 'nl');
    $rows[$i]['civicrm_contact_lang_fr'] = $this->getNumBounces('', '', 'fr');
    $rows[$i]['civicrm_contact_lang_total'] = $this->getNumBounces('', '', '');
    $i++;

    $url  = CRM_Utils_System::url('civicrm/contact/search/custom', ['csid' => 17, 'reset'=> 1]);
    $rows[$i]['civicrm_contact_types_of_member_contact_60'] = "<a href=\"$url\">Ga naar het detailrapport</a>";
    $rows[$i]['civicrm_contact_lang_nl'] = '';
    $rows[$i]['civicrm_contact_lang_fr'] = '';
    $rows[$i]['civicrm_contact_lang_total'] = '';
  }

  private function getNumBounces($memberContact, $bemasFunction, $lang) {
    $sql = CRM_Bemasreporting_BounceSummaryHelper::getSelectForCount($memberContact, $bemasFunction, $lang);
    $num = CRM_Core_DAO::singleValueQuery($sql);

    $urlParams = [
      'reset'=> 1,
      'member' => $memberContact,
      'function' => $bemasFunction,
      'lang' => $lang,
    ];
    $url  = CRM_Utils_System::url('civicrm/bounce_summary_detail', $urlParams);
    return "<a href=\"$url\">$num</a>";
  }
}
