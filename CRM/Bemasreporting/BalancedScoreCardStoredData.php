<?php

class CRM_Bemasreporting_BalancedScoreCardStoredData {
  private $storeValuesOptionGroupID = 0;
  private $storedValues = [];

  /*
   * The values of the BSC can be stored (aka frozen) to prevent small counting differences.
   * The values can be frozen in the option group "Balanced_Score_Card".
   *
   */
  public function __construct($years) {
    $this->initValuesOptionGroupID();

    foreach ($years as $year) {
      $this->initStoredValuesArray($year);
    }
  }

  public function getValue($label, $year) {
    $retval = FALSE;

    if (array_key_exists($year, $this->storedValues)) {
      if (array_key_exists($label, $this->storedValues[$year])) {
        $retval = $this->storedValues[$year][$label];
      }
    }

    return $retval;
  }

  private function initValuesOptionGroupID() {
    // get the option group with stored values
    $sql = "select id from civicrm_option_group where name = 'balanced_score_card'";
    $this->storeValuesOptionGroupID = CRM_Core_DAO::singleValueQuery($sql);
  }

  private function initStoredValuesArray($year) {
    /*************************
     * The values of the BSC from previous years can be stored in the option group "Balanced Score Card".
     * https://www.bemas.org/nl/civicrm/admin/options?reset=1
     *
     * Each entry contains the year in the "name" field, and the values in the "description".
     * Values are in the format: label=value
     * e.g.
     * Terminated member contacts (incl. transfers)=-57
     * Total number of member contacts=722
     * New member companies this period=68
     * ...
     */
    $multiValues = $this->getValuesFromOptionGroup($year);
    $multiValues = $this->removeHTML($multiValues);
    $this->convertToArray($year, $multiValues);
  }

  private function getValuesFromOptionGroup($year) {
    $sql = "
        select
          description
        from
          civicrm_option_value
        where
          option_group_id = %1
        and
          name = %2
        and
          is_active = 1
      ";
    $sqlParams = [
      1 => [$this->storeValuesOptionGroupID, 'Integer'],
      2 => [$year, 'Integer'],
    ];

    $multiValues = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);

    return $multiValues;
  }

  private function removeHTML($multiValues) {
    // remove html stuff
    $multiValues = str_replace('<br />', '|', $multiValues);
    $multiValues = str_replace('<br>', '|', $multiValues);
    $multiValues = str_replace('<p>', '', $multiValues);
    $multiValues = str_replace('</p>', '|', $multiValues);
    $multiValues = str_replace('&gt;', '>', $multiValues);
    $multiValues = str_replace("|\n", "\n", $multiValues);

    return $multiValues;
  }

  private function convertToArray($year, $multiValues) {
    // convert to an array, e.g.
    //   storedValues[2020]['New member contacts this period'] = 81
    //   storedValues[2020]['Terminated member contacts (incl. transfers) '] = -44
    //   ...
    if ($multiValues) {
      // split the string on the newline character
      $valueArr = explode("\n", $multiValues);

      // check all lines
      foreach ($valueArr as $valueString) {
        // ignore the line if we don't have an equal sign
        if (strpos($valueString, '=') !== FALSE) {
          $splittedValue = explode('=', $valueString);
          if (count($splittedValue) == 2) {
            $this->storedValues[$year][trim($splittedValue[0])] = trim($splittedValue[1]);
          }
        }
      }
    }
  }
}
