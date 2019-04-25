<?php

class CRM_Cplabels_Utils {
  public static function sortLabelRows($rows, $labelFormValues) {
    $sort = CRM_Utils_Array::value('cplabel_sort', $labelFormValues);
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
            $api_params['custom_58'] = ['IN' => $service_type];
          }
          $result = civicrm_api3('relationship', 'get', $api_params);
          foreach (CRM_Utils_Array::value('values', $result, array()) as $value) {
            $rows[$value['contact_id_b']]['team_name'] = $value['api.Contact.getsingle']['display_name'];
          }
          uasort($rows, function($a, $b) {
            return strcmp($a['team_name'], $b['team_name']);
          });
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


}