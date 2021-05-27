<?php

class CRM_Bemasreporting_Form_Report_BalancedScoreCard extends CRM_Report_Form {
  const NUMYEARS = 5;
  private $yearsToDisplay = [];
  private $helper;

  public function __construct() {
    $this->helper = new CRM_Bemasreporting_BalancedScoreCardHelper();

    // fill in the array containing the years to display
    $this->fillYearsToDisplay();

    $this->_columns = [
      'balanced_score_card' => [
        'fields' => $this->getEventDashboardFields(),
      ],
    ];

     parent::__construct();
  }

  public function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
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
  }

  public function where() {
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Balanced Scorecard BEMAS vzw-asbl'));
    parent::preProcess();
  }

  function postProcess() {
    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function alterDisplay(&$rows) {
    $bscData = new CRM_Bemasreporting_BalancedScoreCardData();

    $rows = [];
    foreach ($this->helper->rowHeaders as $queryId => $rowHeader) {
      $row = [];

      // add the row header
      if ($rowHeader['is_header']) {
        $row['balanced_score_card_row_header'] = '<strong>' . $rowHeader['label'] . '</strong>';
      }
      else {
        $row['balanced_score_card_row_header'] = $rowHeader['label'];
      }

      // add the value for each year column
      foreach ($this->yearsToDisplay as $year) {
        if ($rowHeader['is_header']) {
          $row['balanced_score_card_' . $year] = '';
        }
        else {
          $method = $rowHeader['method'];
          $value = $bscData->$method($year);
          $url = '<a href="http://localhost/~alain/bemas/nl/civicrm/report/eu.businessandcode.bemasreporting/balancedscorecard-detail?reset=1&qid=' . $queryId . '&year=' . $year . '">' . $value . '</a>';
          $row['balanced_score_card_' . $year] = $url;
        }
      }

      // add the row to the report
      $rows[] = $row;
    }
  }

  private function getEventDashboardFields() {
    $fields = [];

    // the first column has no title (it will contain the row headers)
    $fields['row_header'] = [
      'title' => '',
      'required' => TRUE,
      'dbAlias' => '1',
    ];

    // next, we add columns for every year
    foreach ($this->yearsToDisplay as $year) {
      $fields[$year] = [
        'title' => $year,
        'required' => TRUE,
        'dbAlias' => '1',
      ];
    }

    return $fields;
  }

  private function fillYearsToDisplay() {
    // The BSC contains columns for each year we want to display.
    // Here we initialize the array containing these years.
    // It used to be current year and the 5 previous years, but
    // now we add next year as well.
    $toYear = date('Y') + 1;
    $fromYear = $toYear - self::NUMYEARS;

    for ($i = $fromYear; $i <= $toYear; $i++) {
      $this->yearsToDisplay[] = $i;
    }
  }
}
