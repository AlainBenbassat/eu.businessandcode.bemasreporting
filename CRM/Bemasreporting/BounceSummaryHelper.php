<?php

class CRM_Bemasreporting_BounceSummaryHelper {
  public static function getSelectForCount($memberContactFilter, $BemasFunctionFilter, $langFilter) {
    $selectColumns = 'COUNT(contact_a.id)';
    return self::getSelect($selectColumns, $memberContactFilter, $BemasFunctionFilter, $langFilter);
  }

  public static function getSelectForDetails($memberContactFilter, $BemasFunctionFilter, $langFilter) {
    $selectColumns = "
      contact_a.id,
      contact_a.first_name,
      contact_a.last_name,
      contact_a.organization_name,
      contact_email.email,
      contact_email.hold_date
    ";

    return self::getSelect($selectColumns, $memberContactFilter, $BemasFunctionFilter, $langFilter);
  }

  private static function getSelect($selectColumns, $memberContactFilter, $bemasFunctionFilter, $langFilter) {
    $sql = "
      SELECT
        $selectColumns
      FROM
        civicrm_contact contact_a
      LEFT OUTER JOIN civicrm_email contact_email
          ON contact_a.id = contact_email.contact_id
          AND contact_email.is_primary = 1
      LEFT OUTER JOIN civicrm_value_individual_details_19
          ON civicrm_value_individual_details_19.entity_id = contact_a.id
      WHERE contact_a.contact_type = 'Individual'
        AND contact_a.is_deleted = 0
        AND contact_email.on_hold = 1
        AND contact_email.hold_date >= date_add(NOW(), INTERVAL -1 YEAR)
    ";

    $sql .= self::getMemberContactFilter($memberContactFilter);
    $sql .= self::getBemasFunctionFilter($bemasFunctionFilter);
    $sql .= self::getLangFilter($langFilter);

    return $sql;
  }

  private static function getMemberContactFilter($memberContactFilter) {
    if ($memberContactFilter == 'm1') {
      $PRIMARY_MEMBER_CONTACT = 14;
      $sql = "
        and exists (
          select
            rmc.id
          from
            civicrm_relationship rmc
          where
            rmc.contact_id_a = contact_a.id
          and
            rmc.relationship_type_id = $PRIMARY_MEMBER_CONTACT
          and
            rmc.is_active = 1
        )
      ";
    }
    else if ($memberContactFilter == 'mc') {
      $MEMBER_CONTACT = 15;
      $sql = "
        and exists (
          select
            rmc.id
          from
            civicrm_relationship rmc
          where
            rmc.contact_id_a = contact_a.id
          and
            rmc.relationship_type_id = $MEMBER_CONTACT
          and
            rmc.is_active = 1
        )
      ";
    }
    else if ($memberContactFilter == 'mx') {
      $PRIMARY_MEMBER_CONTACT = 14;
      $MEMBER_CONTACT = 15;
      $sql = " AND
        exists (
          select
            rmc.id
          from
            civicrm_relationship rmc
          where
            rmc.contact_id_a = contact_a.id
          and
            rmc.relationship_type_id in ($PRIMARY_MEMBER_CONTACT, $MEMBER_CONTACT)
          and
            rmc.is_active = 0
        )
      ";
    }
    else {
      $sql = '';
    }

    return $sql;
  }

  private static function getBemasFunctionFilter($bemasFunctionFilter) {
    if ($bemasFunctionFilter) {
      $filter = CRM_Core_DAO::escapeString($bemasFunctionFilter);
      return " AND civicrm_value_individual_details_19.function_28 = '$filter'";
    }

    return '';
  }

  private static function getLangFilter($langFilter) {
    if ($langFilter) {
      $filter = CRM_Core_DAO::escapeString($langFilter);
      return " AND substring(contact_a.preferred_language, -2) = '$filter'";
    }

    return '';
  }
}
