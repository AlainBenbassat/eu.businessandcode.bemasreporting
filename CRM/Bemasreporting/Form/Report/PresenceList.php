<?php

class CRM_Bemasreporting_Form_Report_PresenceList extends CRM_Report_Form {
  protected $_summary = NULL;
  private $eventId = 0;
  private $eventTitle = '';
  private $eventStartDate = '';
  private $eventDates = [];
  private $eventHours = '';


  function __construct() {
    $this->storeEventId();
    $this->storeEventDefaults();

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'first_name' => [
            'title' => ts('First Name'),
            'required' => TRUE,
          ],
          'last_name' => [
            'title' => ts('Last Name'),
            'required' => TRUE,
          ],
          'organization_name' => [
            'title' => ts('Employer'),
            'required' => TRUE,
          ],
          'job_title' => [
            'title' => ts('Job Title'),
            'required' => TRUE,
          ],
          'newsletter' => [
            'title' => 'Ontvangt graag<br>BEMAS nieuwsbrief',
            'required' => TRUE,
            'dbAlias' => "'&nbsp;Ja&nbsp;&nbsp;|&nbsp;&nbsp;Nee&nbsp;'",
          ],
          'sharecontact' => [
            'title' => 'Toestemming voor delen<br>contactgegevens met<br>derden (bv. spreker)',
            'required' => TRUE,
            'dbAlias' => "'&nbsp;Ja&nbsp;&nbsp;|&nbsp;&nbsp;Nee&nbsp;'",
          ],
          'signature' => [
            'title' => 'Handtekening',
            'required' => TRUE,
            'dbAlias' => "'<br><br><br>'",
          ],
        ],
        'filters' => [
          'event' => [
            'title' => ts('Event'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->getEventList(),
            'required' => TRUE,
          ],
          'language' => [
            'title' => ts('Language'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => ['nl' => 'Nederlands', 'fr' => 'Français', 'en' => 'English'],
            'default' => 'en',
            'required' => TRUE,
          ],
          'sign_date' => [
            'title' => 'Datum op lijst',
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->eventDates,
          ],
          'sign_hour' => [
            'title' => 'Van / tot',
            'type' => CRM_Utils_Type::T_STRING,
            'default' => $this->eventHours,
          ],
        ],
      ]
    ];

    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', 'Intekenlijst / FR???');
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = [];

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

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = "
      FROM
        civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
      INNER JOIN
        civicrm_participant p
      ON
        {$this->_aliases['civicrm_contact']}.id = p.contact_id
        AND p.role_id like '%1%' and p.status_id not in (4,7,8,9,10,11,12)
    ";
  }

  function where() {
    $this->_where = " WHERE {$this->_aliases['civicrm_contact']}.is_deleted = 0 and {$this->_aliases['civicrm_contact']}.is_deceased = 0 and event_id = " . $this->getSelectedParam('event_value');

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name ";
  }

  function postProcess() {
    $this->translateColumnHeaders();

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);
//die($sql);
    $rows = [];
    $this->buildRows($sql, $rows);

    $eventDateIndex = $this->getSelectedParam('sign_date_value');
    if ($eventDateIndex) {
      $eventDate = $this->eventDates[$eventDateIndex];
    }
    else {
      $eventDate = $this->eventStartDate;
    }

    $eventHours = $this->getSelectedParam('sign_hour_value');
    if (!$eventHours) {
      $eventHours = $this->eventHours;
    }

    $this->assign('eventTitle', $this->eventTitle);
    $this->assign('eventDate', $eventDate . ' ' . $eventHours);
    $this->assign('labelForRole', $this->getLabelTranslationForRole());
    $this->assign('labelForSpeakerTable', $this->getLabelTranslationForSpeakers());
    $this->assign('labelForParticipantTable', $this->getLabelTranslationForParticipants());

    // get the special roles
    $speakers = $this->getEventSpecialRoles();
    $this->assign('eventSpeakers', $speakers);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    $yes = $this->getLabelForYes();
    $no = $this->getLabelForNo();

    for ($i = 0; $i < count($rows); $i++) {
      $rows[$i]['civicrm_contact_newsletter'] = "&nbsp;$yes&nbsp;&nbsp;|&nbsp;&nbsp;$no&nbsp;";
      $rows[$i]['civicrm_contact_sharecontact'] = "&nbsp;$yes&nbsp;&nbsp;|&nbsp;&nbsp;$no&nbsp;";
    }
  }

  function getEventList() {
    $eventList = [];

    $sql = "
      SELECT
        id
        , concat(
          DATE_FORMAT(start_date, '%d/%m/%Y')
          , ' - '
          , title
        ) event_name
      FROM
        civicrm_event
      WHERE
    ";

    if ($this->eventId > 0) {
      // we have a default event, select it
      $sql .= ' id = ' . $this->eventId;
    }
    else {
      // select all events
      $sql .= "
        start_date >= DATE_FORMAT(now(), '%Y-%m-%d')
      ORDER BY
        start_date
      ";
    }

    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $eventList[$dao->id] = $dao->event_name;
    }

    return $eventList;
  }

  function getSelectedParam($name) {
    if (!empty($this->_params[$name])) {
      return $this->_params[$name];
    }
    elseif (!empty($this->_submitValues[$name])) {
      return $this->_submitValues[$name];
    }
    elseif ($name == 'event_value' && $_SESSION['event_value']) {
       return $_SESSION['event_value'];
    }
    else {
      return '';
    }
  }

  function getEventSpecialRoles() {
    $sql1 = $this->getEventSpecialRolesQuery('trainers');
    $sql2 = $this->getEventSpecialRolesQuery('coaches');

    $names = [];
    $dao = CRM_Core_DAO::executeQuery($sql1 . ' union all ' . $sql2);
    while ($dao->fetch()) {
      $names[] = [
        'role' => $dao->role,
        'first_name' => $dao->first_name,
        'last_name' => $dao->last_name,
        'organization_name' => $dao->organization_name,
        'job_title' => $dao->job_title,
        'newsletter' => $this->getLabelForYes() . ' | ' . $this->getLabelForNo(),
      ];
    }

    return $names;
  }

  private function getEventSpecialRolesQuery($type) {
    if ($type == 'coaches') {
      $label = $this->getLabelForCoaches();
      $roleIdClause = "role_id like '%3%'";
    }
    else {
      $label = $this->getLabelForTrainers();
      $roleIdClause = "(role_id like '%4%' or role_id like '%6%')";
    }

    $sql = "
      select
        '$label' role,
        c.first_name,
        c.last_name,
        c.job_title,
        c.organization_name,
        '' newsletter
      from
        civicrm_participant p
      inner join
        civicrm_contact c on p.contact_id = c.id
      where
        $roleIdClause
        and event_id = {$this->eventId}
        and status_id in (1, 2, 5)
    ";

    return $sql;
  }

  private function storeEventId() {
    $event_id = $this->getEventIdFromUrl();

    if (!$event_id) {
      $event_id = $this->getEventIdFromParticipantIdsInUrl();
    }

    if (!$event_id) {
      $event_id = $this->getSelectedParam('event_value');
    }

    $this->eventId = $event_id ?? 0;
  }

  private function getEventIdFromUrl() {
    if (($event_id = CRM_Utils_Request::retrieve('event_id', 'Positive'))) {
      // OK, store in the session
      $_SESSION['event_value'] = $event_id;

      return $event_id;
    }
    else {
      return 0;
    }
  }

  private function getEventIdFromParticipantIdsInUrl() {
    if (($participant_ids = CRM_Utils_Request::retrieve('participant_ids', 'String'))) {
      $decodedParticipantIds = explode(',', urldecode($participant_ids));
      if (count($decodedParticipantIds) > 0) {
        $event_id = CRM_Core_DAO::singleValueQuery("select event_id from civicrm_participant where id = " . $decodedParticipantIds[0]);
        $_SESSION['event_value'] = $event_id;

        return $event_id;
      }
    }
    else {
      return 0;
    }
  }

  private function storeEventDefaults() {
    if ($this->eventId) {
      $event = civicrm_api3('Event', 'getsingle', ['id' => $this->eventId]);
      $this->eventTitle = $event['title'];
      $this->eventStartDate = CRM_Utils_Date::customFormat($event['start_date'], '%d/%m/%Y');
      $this->eventHours = CRM_Utils_Date::customFormat($event['start_date'], '%H:%i') . ' - ' . CRM_Utils_Date::customFormat($event['end_date'], '%H:%i');
      $this->eventDates = $this->getExtraEventDates($this->eventStartDate, $event);
    }
    else {
      $this->eventStartDate = '';
      $this->eventDates = [];
      $this->eventHours = '';
      $this->eventTitle = '';
    }
  }

  private function getExtraEventDates($eventStartDate, $event) {
    $dateList = [];

    $dateList[] = $eventStartDate;

    $customFieldFromId = 169;
    $customFieldToId = 173;

    for ($i = $customFieldFromId; $i <= $customFieldToId; $i++) {
      if (!empty($event["custom_$i"])) {
        $formattedDate = CRM_Utils_Date::customFormat($event["custom_$i"], '%d/%m/%Y');
        if ($formattedDate != $eventStartDate) {
          $dateList[] = $formattedDate;
        }
      }
    }

    return $dateList;
  }

  function translateColumnHeaders() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang) {
      $translations = $this->getLabelTranslations();

      foreach ($this->_columns['civicrm_contact']['fields'] as $k => $v) {
        $this->_columns['civicrm_contact']['fields'][$k]['title'] = $translations[$k][$lang];
      }
    }
  }

  private function getLabelTranslations() {
    $translations = [];

    $translations['id']['nl'] = 'id';
    $translations['id']['fr'] = 'id';
    $translations['id']['en'] = 'id';

    $translations['first_name']['nl'] = 'Voornaam';
    $translations['first_name']['fr'] = 'Prénom';
    $translations['first_name']['en'] = 'First Name';

    $translations['last_name']['nl'] = 'Achternaam';
    $translations['last_name']['fr'] = 'Nom';
    $translations['last_name']['en'] = 'Last Name';

    $translations['job_title']['nl'] = 'Functie';
    $translations['job_title']['fr'] = 'Fonction';
    $translations['job_title']['en'] = 'Function';

    $translations['organization_name']['nl'] = 'Werkgever';
    $translations['organization_name']['fr'] = 'Employeur';
    $translations['organization_name']['en'] = 'Employer';

    $translations['newsletter']['nl'] = 'Ontvangt graag <br>BEMAS nieuwsbrief';
    $translations['newsletter']['fr'] = 'Recevoir la <br>BEMAS newsletter?';
    $translations['newsletter']['en'] = 'Receive <br>BEMAS newsletter?';

    $translations['sharecontact']['nl'] = 'Toestemming voor delen <br>contactgegevens met derden <br>(bv. spreker)';
    $translations['sharecontact']['fr'] = 'Permission de partager <br>mes données avec <br>partenaires (p.ex. orateur)';
    $translations['sharecontact']['en'] = 'Permission to share <br>my data with partners <br>(e.g. speaker)';

    $translations['signature']['nl'] = 'Handtekening';
    $translations['signature']['fr'] = 'Signature';
    $translations['signature']['en'] = 'Signature';

    return $translations;
  }

  private function getLabelTranslationForRole() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang == 'nl') {
      return 'Rol';
    }
    elseif ($lang == 'fr') {
      return 'Rôle';
    }
    else {
      return 'Role';
    }
  }

  private function getLabelTranslationForSpeakers() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang == 'nl') {
      return 'Organisatie';
    }
    elseif ($lang == 'fr') {
      return 'Organisation';
    }
    else {
      return 'Organization';
    }
  }

  private function getLabelTranslationForParticipants() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang == 'nl') {
      return 'Deelnemers';
    }
    elseif ($lang == 'fr') {
      return 'Participants';
    }
    else {
      return 'Participants';
    }
  }

  function getLabelForYes() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang == 'nl') {
      return 'Ja';
    }
    elseif ($lang == 'fr') {
      return 'Oui';
    }
    else {
      return 'Yes';
    }
  }

  function getLabelForNo() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang == 'nl') {
      return 'Nee';
    }
    elseif ($lang == 'fr') {
      return 'Non';
    }
    else {
      return 'No';
    }
  }

  function getLabelForTrainers() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang == 'nl') {
      $label = 'Spreker/lesgever';
    }
    elseif ($lang == 'fr') {
      $label = 'Orateur/formateur';
    }
    else {
      $label = 'Speaker/trainer';
    }

    return $label;
  }

  function getLabelForCoaches() {
    $lang = $this->getSelectedParam('language_value');
    if ($lang == 'nl') {
      $label = 'Begeleider';
    }
    else if ($lang == 'fr') {
      $label = 'Accompagnateur';
    }
    else {
      $label = 'Coach';
    }

    return $label;
  }

}
