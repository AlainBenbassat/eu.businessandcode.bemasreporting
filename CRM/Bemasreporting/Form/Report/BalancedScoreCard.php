<?php

class CRM_Bemasreporting_Form_Report_BalancedScoreCard extends CRM_Report_Form {
  protected $_summary = NULL;

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'fields' => array(
          'column1' => array(
            'title' => 'Network',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
        ),
      ),
    );

    // add years
    $currentYear = date('Y');
    for ($i = 5; $i >= 0; $i--) {
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

    $row = array();
    $row['civicrm_contact_column1'] = 'New member contacts this period';
    for ($i = 5; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $y;
    }
    $rows[] = $row;

    $row = array();
    $row['civicrm_contact_column1'] = 'Terminated (included transfers)';
    for ($i = 5; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $y;
    }
    $rows[] = $row;

    $row = array();
    $row['civicrm_contact_column1'] = 'Terminated (included transfers)';
    for ($i = 5; $i >= 0; $i--) {
      $y = $currentYear - $i;
      $row['civicrm_contact_' . $y] = $y;
    }
    $rows[] = $row;
  }
}
