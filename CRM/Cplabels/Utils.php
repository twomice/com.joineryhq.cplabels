<?php

class CRM_Cplabels_Utils {
  public static function sortLabelRows($rows, $labelFormValues) {
    // If cplabel_sort isn't defined, either it's not our custom search, or
    // there's just no sorting to do. Do nothing and return rows unchanged.
    $sort = CRM_Utils_Array::value('cplabel_sort', $labelFormValues);
    if (empty($sort)) {
      return $rows;
    }

    // Compile and store some summary data for this set of labels.
    $labelFormHash = CRM_Cplabels_Utils::constructLabelFormHash($labelFormValues);
    $labelSummaryData = CRM_Cplabels_Utils::buildLabelSummaryData($rows);
    CRM_Cplabels_Utils::setSessionVar("labelSummaryData_{$labelFormHash}", $labelSummaryData);

    if ($sort == 'postal_code') {
      uasort($rows, function($a, $b) {
        return strcmp($a['postal_code'], $b['postal_code']);
      });
    }
    elseif ($sort == 'team_name') {
      if ($qfKey = CRM_Utils_Array::value('qfKey', $labelFormValues)) {
        $customSearchSessionValues = CRM_Cplabels_Utils::getSessionVar("formValues_{$qfKey}", array());
        if (!empty($customSearchSessionValues)) {
          $api_params = [
            'sequential' => 1,
            'return' => ["contact_id_a", "contact_id_b"],
            'relationship_type_id' => 17,
            'api.Contact.getsingle' => ['id' => "\$value.contact_id_a", 'return' => ["display_name"]],
            'contact_id_b' => ['IN' => array_keys($rows)],
            'options' => [
              'sort' => 'id DESC',
            ],
          ];
          if ($teams = CRM_Utils_Array::value('team', $customSearchSessionValues)) {
            $api_params['contact_id_a'] = ['IN' => $teams];
          }
          if ($service_type = CRM_Utils_Array::value('service_type', $customSearchSessionValues)) {
            $custom_id = CRM_Cplabels_Utils::getCustomFieldProp('Service_Type', 'Volunteer_details');
            $api_params["custom_{$custom_id}"] = ['IN' => $service_type];
          }
          $result = civicrm_api3('relationship', 'get', $api_params);
          foreach (CRM_Utils_Array::value('values', $result, array()) as $value) {
            $rows[$value['contact_id_b']]['team_name'] = $value['api.Contact.getsingle']['display_name'];
          }
          uasort($rows, function($a, $b) {
            return strcmp($a['team_name'], $b['team_name']);
          });
          // Insert team headers by wasting one label per team.
          $teamName = '';
          $newRows = array();
          foreach ($rows as $cid => $row) {
            if ($row['team_name'] != $teamName) {
              $teamName = $row['team_name'];
              $teamRow = array_fill_keys(array_keys($row), '');
              $teamRow['addressee_display'] = '-------------------';
              $teamRow['street_address'] = $teamName;
              $newRows[$teamName] = $teamRow;
            }
            $newRows[$cid] = $row;
          }
          $rows = $newRows;
        }
      }
    }
    return $rows;
  }

  public static function arrayToSqlInValues($arr = array(), $type, $emptyValue = "''") {
    if (empty($arr)) {
      return $emptyValue;
    }
    $placeholders = $params = [];
    $i = 1;
    foreach ($arr as $value){
      $placeholders[] = "%$i";
      $params[$i] = array($value, $type);
      $i++;
    }
    if (!empty($placeholders)) {
      $query = implode(', ', $placeholders);
    }
    return CRM_Core_DAO::composeQuery($query, $params);
  }

  public static function setSessionVar($name, $value) {
    $_SESSION['com.joineryhq.cplabels'][$name] = $value;
  }

  public static function getSessionVar($name, $default = NULL) {
    return CRM_Utils_Array::value($name, $_SESSION['com.joineryhq.cplabels'], $default);
  }

  public static function constructLabelFormHash($formValues) {
    $hashValues = array(
      CRM_Utils_Array::value('qfKey', $formValues, ''),
      CRM_Utils_Array::value('location_type_id', $formValues, ''),
      CRM_Utils_Array::value('do_not_mail', $formValues, ''),
      CRM_Utils_Array::value('merge_same_address', $formValues, ''),
      CRM_Utils_Array::value('merge_same_household', $formValues, ''),
    );
    return implode('|', $hashValues);
  }

  public function buildLabelSummaryData($rows) {
    $uniques = array();
    $zipcounts = array();
    foreach ($rows as $cid => $row) {
      $key = implode(', ', array(
        CRM_Utils_Array::value('street_address', $row),
        CRM_Utils_Array::value('city', $row),
        CRM_Utils_Array::value('state_province', $row),
        CRM_Utils_Array::value('postal_code', $row),
        CRM_Utils_Array::value('country', $row),
      ));
      $uniques[$key][] = CRM_Utils_Array::value('display_name', $row) . " ($cid)";

      $zipcounts[substr(CRM_Utils_Array::value('postal_code', $row), 0, 3)]++;
    }
    $duplicates = array_filter($uniques, function($value){
      return count($value) > 1;
    });

    return array(
      'duplicates' => $duplicates,
      'zipcounts' => $zipcounts,
      'total' => count($rows),
    );
  }

  public static function getCustomFieldProp($fieldName, $groupName, $prop = 'id') {
    $api_params = array(
      'name' => $fieldName,
      'custom_group' => $groupName,
      'return' => $prop,
    );
    return civicrm_api3('customField', 'getValue', $api_params);

  }

  public static function getCustomGroupProp($groupName, $prop = 'table_name') {
    $api_params = array(
      'name' => $groupName,
      'return' => $prop,
    );
    return civicrm_api3('customGroup', 'getValue', $api_params);

  }

}