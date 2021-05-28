<?php

class CRM_Bemasreporting_Form_Report_BalancedScoreCardDetail extends CRM_Report_Form {
  private $helper;

  public function __construct() {
    $this->helper = new CRM_Bemasreporting_BalancedScoreCardHelper();

    $this->_columns = [
      'balanced_score_card' => [
        'fields' => $this->getEventDashboardFields(),
      ],
    ];

    // we need to do the following because I don't know how reports handle/removes query params
    $this->getQueryIdentifierFromUrl();
    $this->getYearFromUrl();

    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Balanced Scorecard Detail'));
    parent::preProcess();
  }

  public function select() {
    // get the sql query from our data class
    $bscData = new CRM_Bemasreporting_BalancedScoreCardData();
    $bscData->setReturnModeAllRecords();

    // get the query identifier and the year from the url
    $queryId = $this->getQueryIdentifierFromUrl();
    $year = $this->getYearFromUrl();
    $method = $this->helper->rowHeaders[$queryId]['method'];

    // get the sql statement
    $sql = $bscData->$method($year);
    $this->_select = $sql;
  }

  public function from() {
    $this->_from = "";
  }

  public function where() {
    $this->_where = "";
  }

  function postProcess() {
    parent::postProcess();
/*
     $this->beginPostProcess();
    // get the sql query from our data class
    $bscData = new CRM_Bemasreporting_BalancedScoreCardData();
    $bscData->setReturnModeAllRecords();

    // get the query identifier and the year from the url
    $queryId = $this->getQueryIdentifierFromUrl();
    $year = $this->getYearFromUrl();
    $method = $this->helper->rowHeaders[$queryId]['method'];

    // get the sql statement
    $sql = $bscData->$method($year);

    // show the name of the query and year
    $this->assign('reportSubTitle', $this->helper->rowHeaders[$queryId]['label'] . ' - ' . $year);

    $sql = $this->buildQuery(TRUE);
    $rows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);*/
  }

  public function alterDisplay(&$rows) {
  }

  private function getEventDashboardFields() {
    $fields = [];

    $fields['id'] = [
      'title' => 'ID',
      'required' => TRUE,
    ];
    $this->_columnHeaders['balanced_score_card_id']['title'] = $fields['id']['title'];

    $fields['display_name'] = [
      'title' => 'Weergavenaam',
      'required' => TRUE,
    ];
    $this->_columnHeaders['balanced_score_card_display_name']['title'] = $fields['display_name']['title'];

    return $fields;
  }

  private function getQueryIdentifierFromUrl() {
    // although we pass the param in the query string, it is removed after submit
    // don't know how to handle properly, so I store it in the session
    $v = CRM_Utils_Request::retrieveValue('qid', 'String', '', FALSE, 'GET');
    if ($v) {
      $_SESSION['bsc_qid'] = $v;
    }
    elseif (isset($_SESSION['bsc_qid'])) {
      $v = $_SESSION['bsc_qid'];
    }
    else {
      $v = 'Q2';
    }

    return $v;
  }

  private function getYearFromUrl() {
    // although we pass the param in the query string, it is removed after submit
    // don't know how to handle properly, so I store it in the session
    $v = CRM_Utils_Request::retrieveValue('year', 'Integer', '', FALSE, 'GET');
    if ($v) {
      $_SESSION['bsc_year'] = $v;
    }
    elseif (isset($_SESSION['bsc_year'])) {
      $v = $_SESSION['bsc_year'];
    }
    else {
      $v = date('Y');
    }

    return $v;
  }
}
