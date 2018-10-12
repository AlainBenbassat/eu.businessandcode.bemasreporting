<?php

class CRM_Bemasreporting_Form_Report_PresenceList extends CRM_Report_Form {
  protected $_summary = NULL;
  private $translations = [];

  function __construct() {
    // see if we have an event id
    if (($event_id = CRM_Utils_Request::retrieve('event_id', 'Positive'))) {
       // OK, found in the url
    }
    else {
      $event_id = 0;
    }

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
            'required' => TRUE,
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
            'required' => TRUE,
          ),
          'job_title' => array(
            'title' => ts('Job Title'),
            'required' => TRUE,
          ),
          'organization_name' => array(
            'title' => ts('Employer'),
            'required' => TRUE,
          ),
          'newsletter' => array(
            'title' => 'Ontvangt graag<br>BEMAS nieuwsbrief',
            'required' => TRUE,
            'dbAlias' => "'&nbsp;Ja&nbsp;&nbsp;|&nbsp;&nbsp;Nee&nbsp;'",
          ),
          'sharecontact' => array(
            'title' => 'Toestemming voor delen<br>contactgegevens met<br>derden (bv. spreker)',
            'required' => TRUE,
            'dbAlias' => "'&nbsp;Ja&nbsp;&nbsp;|&nbsp;&nbsp;Nee&nbsp;'",
          ),
          'signature' => array(
            'title' => 'Handtekening',
            'required' => TRUE,
            'dbAlias' => "'<br><br><br>'",
          ),
        ),
        'filters' => array(
          'event' => array(
            'title' => ts('Event'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->getEventList($event_id),
            'required' => TRUE,
          ),
          'language' => array(
            'title' => ts('Language'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => ['nl' => 'Nederlands', 'fr' => 'Français', 'en' => 'English'],
            'required' => TRUE,
          ),
        ),
      )
    );

    // set translations
    $this->translations['id']['nl'] = 'id';
    $this->translations['id']['fr'] = 'id';
    $this->translations['id']['en'] = 'id';

    $this->translations['first_name']['nl'] = 'Voornaam';
    $this->translations['first_name']['fr'] = 'Prénom';
    $this->translations['first_name']['en'] = 'First Name';

    $this->translations['last_name']['nl'] = 'Achternaam';
    $this->translations['last_name']['fr'] = 'Nom';
    $this->translations['last_name']['en'] = 'Last Name';

    $this->translations['job_title']['nl'] = 'Functie';
    $this->translations['job_title']['fr'] = 'Fonction';
    $this->translations['job_title']['en'] = 'Function';

    $this->translations['organization_name']['nl'] = 'Werkgever';
    $this->translations['organization_name']['fr'] = 'Employeur';
    $this->translations['organization_name']['en'] = 'Employer';

    $this->translations['newsletter']['nl'] = 'Ontvangt graag<br>BEMAS nieuwsbrief';
    $this->translations['newsletter']['fr'] = 'Recevoir la<br>BEMAS newsletter?';
    $this->translations['newsletter']['en'] = 'Receive<br>BEMAS newsletter?';

    $this->translations['sharecontact']['nl'] = 'Toestemming voor delen<br>contactgegevens met derden<br>(bv. spreker)';
    $this->translations['sharecontact']['fr'] = 'Permission de partager<br>mes données avec<br>partenaires (p.ex. orateur)';
    $this->translations['sharecontact']['en'] = 'Permission to share<br>my data with partners<br>(e.g. speaker)';

    $this->translations['signature']['nl'] = 'Handtekening';
    $this->translations['signature']['fr'] = 'Signature';
    $this->translations['signature']['en'] = 'Signature';

    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', 'Intekenlijst / FR???');
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
        AND p.role_id = 1 and p.status_id in (1, 2, 5) 
    ";
  }

  function where() {
    $this->_where = " WHERE event_id = " . $this->_params['event_value'];

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
    // translate column labels
    if (array_key_exists('language_value', $this->_submitValues) && $this->_submitValues['language_value']) {
      $this->translateColumnHeaders($this->_submitValues['language_value']);
    }

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);
//die($sql);
    $rows = array();
    $this->buildRows($sql, $rows);

    // get the selected event
    $params = ['id' => $this->_params['event_value']];
    $event = civicrm_api3('Event', 'getsingle', $params);
    $eventDate = date_format(date_create($event['start_date']), 'd/m/Y');
    $this->assign('eventTitle', $event['title']);
    $this->assign('eventDate', $eventDate);

    // get the special roles
    $speakers = $this->getEventSpecialRoles(4, $this->_params['event_value'], $this->_params['language_value']);
    $coaches = $this->getEventSpecialRoles(3, $this->_params['event_value'], $this->_params['language_value']);
    $this->assign('eventSpeakers', $speakers);
    $this->assign('eventCoaches', $coaches);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    if ($this->_submitValues['language_value'] == 'nl') {
      $yes = 'Ja';
      $no = 'Nee';
    }
    else if ($this->_submitValues['language_value'] == 'fr') {
      $yes = 'Oui';
      $no = 'Non';
    }
    else {
      $yes = 'Yes';
      $no = 'No';
    }

    for ($i = 0; $i < count($rows); $i++) {
      $rows[$i]['civicrm_contact_newsletter'] = "&nbsp;$yes&nbsp;&nbsp;|&nbsp;&nbsp;$no&nbsp;";
      $rows[$i]['civicrm_contact_sharecontact'] = "&nbsp;$yes&nbsp;&nbsp;|&nbsp;&nbsp;$no&nbsp;";
    }
  }

  function getEventList($event_id) {
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

    if ($event_id > 0) {
      // we have a default event, select it
      $sql .= " id = $event_id";
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

  function getEventSpecialRoles($roleID, $eventID, $lang) {
    $label = '';

    if ($roleID == 3) {
      if ($lang == 'nl') {
        $label = 'Begeleider(s): ';
      }
      else if ($lang == 'fr') {
        $label = 'Accompagnateur(s): ';
      }
      else {
        $label = 'Coach(es): ';
      }
    }
    else if ($roleID == 4) {
      if ($lang == 'nl') {
        $label = 'Spreker(s): ';
      }
      else if ($lang == 'fr') {
        $label = 'Orateur(s): ';
      }
      else {
        $label = 'Speaker(s): ';
      }
    }

    $sql = "
      select
        concat(c.first_name, ' ', c.last_name) names
      from
        civicrm_participant p
      inner join
        civicrm_contact c on p.contact_id = c.id
      where
        role_id = $roleID and event_id = $eventID
        and status_id in (1, 2, 5)
      order by
        sort_name
    ";

    $names = [];
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $names[] = $dao->names;
    }

    return $label . implode(', ', $names);
  }

  function translateColumnHeaders($lang) {
    foreach ($this->_columns['civicrm_contact']['fields'] as $k => $v) {
      $this->_columns['civicrm_contact']['fields'][$k]['title'] = $this->translations[$k][$lang];
    }
  }
}
