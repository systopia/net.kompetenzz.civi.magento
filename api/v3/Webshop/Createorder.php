<?php
/*-------------------------------------------------------+
| Komptenzzentrum Webshop (Magento) Anbindung            |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


/**
 * @todo doc
 */
function civicrm_api3_webshop_createorder($params) {
  $activity_data = array(
    // TODO: leave target_id empty if organisation_id not set?
    'target_id'          => empty($params['organisation_id']) ? $params['contact_id'] : $params['organisation_id'],
    'activity_type_id'   => CRM_Core_OptionGroup::getValue('activity_type', 'ws_order', 'name'),
    'subject'            => $params['subject'],
    'activity_date_time' => date('YmdHis'), // default, can be overwritten below
    'source_contact_id'  => $params['contact_id'],
    'status_id'          => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name'),
  );

  if (!empty($params['activity_date_time'])) {
    // try parsing the date string
    $activity_date_time = strtotime($params['activity_date_time']);
    if ($activity_date_time) {
      $activity_data['activity_date_time'] = date('YmdHis', $activity_date_time);
    }
  }

  if (!empty($params['details'])) {
    $activity_data['details'] = $params['details'];
  }

  if (!empty($params['magento_order_id'])) {
    $activity_data['wsorder.order_external_identifier'] = $params['magento_order_id'];
  }

  CRM_Magento_CustomData::resolveCustomFields($activity_data);
  return civicrm_api3('Activity', 'create', $activity_data);
}

/**
 * API3 action specs
 *
 * @todo implement properly
 */
function _civicrm_api3_webshop_createorder_spec(&$params) {
  $params['contact_id'] = array(
    'name'         => 'contact_id',
    'title'        => 'Contact',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => 'Which contact should the order be recorded for?',
    );
  $params['organisation_id'] = array(
    'name'         => 'organisation_id',
    'title'        => 'Contact\'s organisation',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description'  => 'Which contact should the order be assigned to?',
    );
  $params['subject'] = array(
    'name'         => 'subject',
    'title'        => 'Subject',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.default'  => 'Webshop Order',
    'description'  => 'Order Subject Line',
    );
  $params['details'] = array(
    'name'         => 'details',
    'title'        => 'Details',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.default'  => 'angelegt von Magento Webshop API',
    'description'  => 'Order Details',
    );
  $params['activity_date_time'] = array(
    'name'         => 'activity_date_time',
    'title'        => 'Order date',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Date to be stored in the order activity.',
    );
  $params['magento_order_id'] = array(
    'name'         => 'magento_order_id',
    'title'        => 'Order ID',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Magento order ID',
    );
}

