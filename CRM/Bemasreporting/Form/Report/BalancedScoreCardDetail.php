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

    // show the the query name and year
    CRM_Core_Session::setStatus($this->helper->rowHeaders[$queryId]['label'] . ' - ' . $year, '', 'no-popup');
  }

  public function from() {
    $this->_from = "";
  }

  public function where() {
    $this->_where = "";
  }

  function postProcess() {
    parent::postProcess();
  }

  public function alterDisplay(&$rows) {
  }

  public function buildQuery($applyLimit = TRUE) {
    parent::buildQuery($applyLimit);

    // civi adds too many SQL_CALC_FOUND_ROWS, just keep the first one
    $str = $this->_select;
    $pos = strpos($str,'SQL_CALC_FOUND_ROWS');
    $numChars = strlen('SQL_CALC_FOUND_ROWS');
    if ($pos !== FALSE) {
      $str = substr($str,0,$pos + $numChars) . str_replace('SQL_CALC_FOUND_ROWS','', substr($str,$pos + $numChars));
      $this->_select = $str;
    }

    return $this->_select;
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
