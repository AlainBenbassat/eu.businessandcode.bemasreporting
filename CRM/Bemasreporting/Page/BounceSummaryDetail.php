<?php
use CRM_Bemasreporting_ExtensionUtil as E;

class CRM_Bemasreporting_Page_BounceSummaryDetail extends CRM_Core_Page {

  public function run() {
    $memberContact = $this->getUrlParam('member');
    $bemasFunction = $this->getUrlParam('function');
    $lang = $this->getUrlParam('lang');

    CRM_Utils_System::setTitle('Bounces');

    $sql = CRM_Bemasreporting_BounceSummaryHelper::getSelectForDetails($memberContact, $bemasFunction, $lang);
    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = $dao->fetchAll();

    $this->assign('rows', $rows);

    $filterString = $this->getReadableFilterDescription($memberContact, $bemasFunction, $lang);
    $this->assign('filter_description', $filterString);

    parent::run();
  }

  private function getUrlParam($paramName) {
    return CRM_Utils_Request::retrieve($paramName, 'String');
  }

  private function getReadableFilterDescription($memberContact, $bemasFunction, $lang) {
    $filters = [];

    if ($memberContact) {
      $filters[] = "soort lidcontact: $memberContact";
    }

    if ($bemasFunction) {
      $filters[] = "BEMAS functie = $bemasFunction";
    }

    if ($lang) {
      $filters[] = "taal = $lang";
    }

    return implode(', ', $filters);
  }
}
