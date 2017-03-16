<?php

class CRM_Bemasreporting_Form_Report_EventDashboard extends CRM_Report_Form {
  protected $_addressField = FALSE;
  protected $_emailField = FALSE;
  protected $_summary = NULL;
  protected $_exposeContactID = FALSE;

  function __construct() {
    $this->_columns = array(
      'civicrm_event' => array(
        'dao' => 'CRM_Event_DAO_Event',
        'fields' => array(
          'id' => array(
            'title' => 'event_id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'start_date' => array(
            'title' => 'Datum',
            'required' => TRUE,
            'dbAlias' => 'date_format(start_date, \'%Y-%m-%d\')',
          ),
          'title' => array(
            'title' => ts('Activiteit'),
            'required' => TRUE,
          ),
          'registered_attended' => array(
            'title' => ts('Ingeschreven/Deelgenomen'),
            'required' => TRUE,
            'dbAlias' => '-1',
          ),
          'cancel_noshow' => array(
            'title' => ts('Niet gekomen/geannuleerd'),
            'required' => TRUE,
            'dbAlias' => '-1',
          ),
          'invoiced' => array(
            'title' => ts('Gefactureerd'),
            'required' => TRUE,
            'dbAlias' => '-1',
          ),
          'fee_to_be_invoiced' => array(
            'title' => ts('Te factureren bedrag'),
            'required' => TRUE,
            'dbAlias' => '-1',
          ),
          'fee_invoiced' => array(
            'title' => ts('Gefactureerd bedrag'),
            'required' => TRUE,
            'dbAlias' => '-1',
          ),
        ),
        'filters' => array(
          'start_date' => array(
            'title' => 'Datum',
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'event_type' => array(
            'name' => 'event_type_id',
            'title' => 'Evenementtype',
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ),
        ),
      ),
      'civicrm_event_custom' => array(
        'filters' => array(
          'volledige_afgehandeld_en_gefactu_79' => array(
            'title' => 'Volledig afgehandeld en gefactureerd?',
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ),
        ),
      ),
    );

    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Activiteiten dashboard'));
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
    $this->_from = " FROM  civicrm_event {$this->_aliases['civicrm_event']} 
      LEFT OUTER JOIN civicrm_value_activiteit_status_25 {$this->_aliases['civicrm_event_custom']}
      ON {$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_event_custom']}.entity_id";
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    // add a start_date filter if not specified
    if (strpos($this->_where, 'start_date') === FALSE) {
      $from = date('Y-m-d', time() - (86400 * 30)); // current date - 30 days
      $to = date('Y-m-d', time() + (86400 * 45)); // current date + 45 days
      $this->_where .= " AND start_date between '$from' and '$to' ";
    }
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_event']}.start_date DESC";
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

  function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
      // change title in a hyperlink
      $eventLink = CRM_Utils_System::baseURL() . 'civicrm/event/manage/settings?reset=1&action=update&id=' . $row['civicrm_event_id'];
      $url = "<a href=\"$eventLink\">{$row['civicrm_event_title']}</a>";
      $rows[$rowNum]['civicrm_event_title'] = $url;

      // count the registered participants (= everything but no show and cancel
      $statusIDs = '3,4';
      $sql = "select count(*) count_result, ifnull(sum(ifnull(fee_amount, 0) - ifnull(discount_amount, 0)), 0) fee_result from civicrm_participant p inner join civicrm_contact c on c.id = p.contact_id where c.is_deleted = 0 and 
        p.event_id = " . $row['civicrm_event_id'] . " and p.status_id not in (" . $statusIDs . ")";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $dao->fetch();
      $rows[$rowNum]['civicrm_event_registered_attended'] = $dao->count_result;
      $rows[$rowNum]['civicrm_event_fee_to_be_invoiced'] = $dao->fee_result;

      // count the canceled and no-show participants
      $statusIDs = '3,4';
      $sql = "select count(*) from civicrm_participant p inner join civicrm_contact c on c.id = p.contact_id where c.is_deleted = 0 and 
        p.event_id = " . $row['civicrm_event_id'] . " and p.status_id in (" . $statusIDs . ")";
      $count = CRM_Core_DAO::singleValueQuery($sql);
      $rows[$rowNum]['civicrm_event_cancel_noshow'] = $count;

      // count the invoiced participants
      $statusIDs = '16';
      $sql = "select count(*) count_result, ifnull(sum(ifnull(fee_amount, 0) - ifnull(discount_amount, 0)), 0) fee_result from civicrm_participant p inner join civicrm_contact c on c.id = p.contact_id where c.is_deleted = 0 and 
        p.event_id = " . $row['civicrm_event_id'] . " and p.status_id = " . $statusIDs;
      $dao = CRM_Core_DAO::executeQuery($sql);
      $dao->fetch();
      $rows[$rowNum]['civicrm_event_invoiced'] = $dao->count_result;
      $rows[$rowNum]['civicrm_event_fee_invoiced'] = $dao->fee_result;
    }
  }
}
