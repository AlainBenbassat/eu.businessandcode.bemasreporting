<?php

class CRM_Bemasreporting_Task_ShowPresenceList extends CRM_Event_Form_Task {

  public function __construct() {
    $queryParams = [];
    $queryParams[] = 'reset=1';
    $queryParams[] = 'output=criteria';

    $event_id = $this->getEventId();
    if ($event_id > 0) {
      $queryParams[] = 'event_id=' . $event_id;
    }

    // redirect to the presence report instance (i.e. id = 61)
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/report/instance/61', implode('&', $queryParams)));

    parent::__construct();
  }

  private function getEventId() {
    // the event_id can be found everywhere...

    $event_id = $this->getEventIdFromEntryUrl();

    if (!$event_id) {
      $event_id = $this->getEventIdFromPreviousSearch();
    }

    return $event_id;
  }

  private function getEventIdFromEntryUrl() {
    $event_id = 0;

    $session = CRM_Core_Session::singleton();
    $entryURL = $session->get('entryURL');
    if (strpos($entryURL, '&amp;event=')) {
      $urlParts = explode('&amp;', $entryURL);
      foreach ($urlParts as $urlPart) {
        $splitUrlPart = explode('=', $urlPart);
        if ($splitUrlPart[0] == 'event') {
          $event_id = $splitUrlPart[1];
          break;
        }
      }
    }

    return $event_id;
  }

  private function getEventIdFromPreviousSearch() {
    $event_id = 0;

    $session = CRM_Core_Session::singleton();
    $allVars = [];
    $session->getVars($allVars);
    foreach ($allVars as $sessionKey => $sessionValue) {
      if (strpos($sessionKey, 'CRM_Event_Controller_Search_') !== FALSE) {
        if (!empty($sessionValue['formValues']['event_id'])) {
          $event_id = $sessionValue['formValues']['event_id'];
          break;
        }
      }
    }

    return $event_id;
  }

}
