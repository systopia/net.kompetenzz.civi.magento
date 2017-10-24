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
function civicrm_api3_webshop_submit($params) {
  // 1) input sanitation / verification
  // check location type
  $location_type = civicrm_api3('LocationType', 'getsingle', array(
    'id' => $params['location_type_id']));
  if ($location_type['is_error'] == 1) {
    return civicrm_api3_create_error('Non-existing location type.');
  }

  // check orders
  if (!empty($params['wsorders']) && is_string($params['wsorders'])) {
    // try to decode JSON string
    $params['wsorders'] = json_decode($params['wsorders'], TRUE);
  }
  if (!is_array($params['wsorders'])) {
    return civicrm_api3_create_error("Field 'wsorders' is not an array.");
  }

  // 2) contact lookup
  $params['individual_individual_prefix'] = $params['individual_prefix'];
  // Exclude address for now, as we are checking organisation address first.
  if (!empty($params['organisation_street_address'])) {
    // TODO: This may include a check for all address components.
    $individual_submitted_address = array();
    foreach (array(
               'individual_street_address','individual_postal_code',
               'individual_city',
               'individual_country',
             ) as $individual_address_component) {
      if (!empty($params[$individual_address_component])) {
        $individual_submitted_address[$individual_address_component] = $params[$individual_address_component];
        unset($params[$individual_address_component]);
      }
    }
  }
  $contact_id = CRM_Magento_Submission::getContact('Individual', 'individual_', $params);

  // organisation lookup
  if (!empty($params['organisation_name'])) {
    $params['organisation_organization_name'] = $params['organisation_name'];
    $organisation_id = CRM_Magento_Submission::getContact('Organization', 'organisation_', $params);
  }
  $address_shared = (isset($organisation_id) ? CRM_Magento_Submission::shareWorkAddress($contact_id, $organisation_id) : FALSE);

  // Address is not shared, use submitted address.
  if (!$address_shared && !empty($individual_submitted_address)) {
    $individual_submitted_address['contact_id'] = $contact_id;
    $individual_submitted_address['location_type_id'] = $params['location_type_id'];
    civicrm_api3('Address', 'create', $individual_submitted_address);
  }

  // relationship update/creation
  if (isset($organisation_id)) {
    // TODO: should a relationship be created without department field??
    CRM_Magento_Submission::updateEmployerRelation($contact_id, $organisation_id, CRM_Utils_Array::value('department', $params, ''));
  }

  // create ws orders
  $orders = array();
  foreach ($params['wsorders'] as $order_data) {
    // if contact_id is previously set, the values are probably wrong anyway ... overwrite!
    $order_data['contact_id'] = $contact_id;
    if (isset($organisation_id)) {
      $order_data['organisation_id'] = $organisation_id;
    }

    // Create order via Webshop API.
    $order = civicrm_api3('Webshop', 'createorder', $order_data);
    $orders[] = $order['id'];
  }

  $foo = NULL;
  $success_data = array(
    'individual_id' => $contact_id,
  );
  if (isset($organisation_id)) {
    $success_data['organization_id'] = $organisation_id;
  }
  return civicrm_api3_create_success($orders, array(), NULL, NULL, $foo, $success_data);
}

/**
 * API3 action specs
 *
 * @todo implement properly
 */
function _civicrm_api3_webshop_submit_spec(&$params) {
  $params['location_type_id'] = array(
    'name'         => 'location_type_id',
    'title'        => 'Location Type',
    'type'         => CRM_Utils_Type::T_INT,
    'api.default'  => 2,
    'description'  => 'Defines the location type to be used for address, email, phone',
    );

  // Main contact data
  $params['individual_first_name'] = array(
    'name'         => 'individual_first_name',
    'title'        => 'First Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s first name',
    );
  $params['individual_last_name'] = array(
    'name'         => 'individual_last_name',
    'title'        => 'Last Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s last name',
    );
  $params['individual_prefix'] = array(
    'name'         => 'individual_prefix',
    'title'        => 'Prefix',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'Contact\'s prefix',
    );
  $params['individual_email'] = array(
    'name'         => 'individual_email',
    'title'        => 'Email',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'Contact\'s email',
    );
  $params['individual_phone'] = array(
    'name'         => 'individual_phone',
    'title'        => 'Phone number',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s phone',
    );
  $params['individual_fax'] = array(
    'name'         => 'individual_fax',
    'title'        => 'Fax number',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s fax',
    );
  $params['individual_street_address'] = array(
    'name'         => 'individual_street_address',
    'title'        => 'Street address',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s street_address',
    );
  $params['individual_postal_code'] = array(
    'name'         => 'individual_postal_code',
    'title'        => 'Postal / ZIP code',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s postal_code',
    );
  $params['individual_city'] = array(
    'name'         => 'individual_city',
    'title'        => 'City',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s city',
    );
  $params['individual_country'] = array(
    'name'         => 'individual_country',
    'title'        => 'Country',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Contact\'s country',
    );

  // Organisation data
  $params['organisation_name'] = array(
    'name'         => 'organisation_name',
    'title'        => 'Organisation Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation name',
    );
  $params['organisation_contact_sub_type'] = array(
    'name'         => 'organisation_contact_sub_type',
    'title'        => 'Organisation Type',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation\'s contact subtype',
    );
  // $params['organisation_email'] = array(
  //   'name'         => 'organisation_email',
  //   'title'        => 'Email',
  //   'api.required' => 1,
  //   'description'  => 'Organisation\'s email',
  //   );
  $params['organisation_phone'] = array(
    'name'         => 'organisation_phone',
    'title'        => 'Organisation phone number',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation\'s phone',
    );
  $params['organisation_fax'] = array(
    'name'         => 'organisation_fax',
    'title'        => 'Organisation fax number',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation\'s fax',
    );
  $params['organisation_street_address'] = array(
    'name'         => 'organisation_street_address',
    'title'        => 'Organisation street address',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation\'s street_address',
    );
  $params['organisation_postal_code'] = array(
    'name'         => 'organisation_postal_code',
    'title'        => 'Organisation postal / ZIP code',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation\'s postal_code',
    );
  $params['organisation_city'] = array(
    'name'         => 'organisation_city',
    'title'        => 'Organisation city',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation\'s city',
    );
  $params['organisation_country'] = array(
    'name'         => 'organisation_country',
    'title'        => 'Organisation country',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Organisation\'s country',
    );

  // Department (for employer relationship)
  $params['department'] = array(
    'name'         => 'department',
    'title'        => 'Department',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'Department (for employer relationship)',
    );

  // Orders
  $params['wsorders'] = array(
    'name'         => 'wsorders',
    'title'        => 'WS Orders',
    'api.required' => 0,
    'description'  => 'Array of orders (TODO)',
    );
}

