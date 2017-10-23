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


define('EMPLOYER_RELATIONSHIP_TYPE_ID', 5);
define('LOCATION_TYPE_ID_WORK', 2);
define('PHONE_TYPE_ID_FAX', 3);


class CRM_Magento_Submission {

  /**
   * Assemble the contact data wrt prefix and pass it to the XCM for matchign
   */
  public static function getContact($contact_type, $prefix, $data) {
    $contact_data = array();

    // extract data where prefix matches
    $prefix_length = strlen($prefix);
    foreach ($data as $key => $value) {
      if ($prefix == substr($key, 0, $prefix_length)) {
        $contact_data[substr($key, $prefix_length)] = $value;
      }
    }

    // if no parameters given, do nothing
    if (empty($contact_data)) {
      return NULL;
    }

    // prepare values: country
    if (!empty($contact_data['country'])) {
      if (is_numeric($contact_data['country'])) {
        $contact_data['country_id'] = $contact_data['country'];
        unset($contact_data['country']);
      } else {
        // look up
        $country = civicrm_api3('Country', 'get', array('iso_code' => $contact_data['country']));
        if (!empty($country['id'])) {
          $contact_data['country_id'] = $country['id'];
          unset($contact_data['country']);
        } else {
          throw new API_Exception("Unknown country '{$contact_data['country']}'", 1);
        }
      }
    }

    // pass to XCM
    $contact_data['contact_type'] = $contact_type;
    $contact = civicrm_api3('Contact', 'getorcreate', $contact_data);
    if (empty($contact['id'])) {
      return NULL;
    }

    // add fax number if submitted
    if (empty($contact_data['fax'])) {
      $existing_faxes = civicrm_api3('Phone', 'get', array(
        'phone_type_id' => PHONE_TYPE_ID_FAX,
        'phone'         => $contact_data['fax'],
        'contact_id'    => $contact_id));

      if (empty($existing_faxes['count'])) {
        // doesn't exist yet => create
        civicrm_api3('Phone', 'create', array(
          'phone_type_id'    => PHONE_TYPE_ID_FAX,
          'phone'            => $contact_data['fax'],
          'location_type_id' => LOCATION_TYPE_ID_WORK,
          'contact_id'       => $contact_id));
      }
    }

    return $contact['id'];
  }

  /**
   * Share an organisation's work address, unless the contact already has one
   */
  public static function shareWorkAddress($contact_id, $organisation_id) {
    if (empty($organisation_id)) {
      // only if organisation exists
      return;
    }

    // check if organisation has a WORK address
    $existing_org_addresses = civicrm_api3('Address', 'get', array(
      'contact_id'       => $organisation_id,
      'location_type_id' => LOCATION_TYPE_ID_WORK));
    if ($existing_org_addresses['count'] <= 0) {
      // organisation doens't have a WORK address
      return;
    }

    // check if contact already has a WORK address
    $existing_contact_addresses = civicrm_api3('Address', 'get', array(
      'contact_id'       => $contact_id,
      'location_type_id' => LOCATION_TYPE_ID_WORK));
    if ($existing_contact_addresses['count'] > 0) {
      // contact already has a WORK address
      return;
    }

    // create a shared address
    $address = reset($existing_org_addresses['values']);
    $address['contact_id'] = $contact_id;
    $address['master_id']  = $address['id'];
    unset($address['id']);
    civicrm_api3('Address', 'create', $address);
  }

  /**
   * should adjust/create the employer relationship between contact and organisation
   */
  public static function updateEmployerRelation($contact_id, $organisation_id, $department = NULL) {
    if (empty($contact_id) || empty($organisation_id)) return;

    // see if there is already one
    $existing_relationship = civicrm_api3('Relationship', 'get', array(
      'relationship_type_id' => EMPLOYER_RELATIONSHIP_TYPE_ID,
      'contact_id_a'         => $contact_id,
      'contact_id_b'         => $organisation_id,
      'is_active'            => 1));

    if ($existing_relationship['count'] == 0) {
      // there is currenlty no (active) relationship between these two
      $new_relationship_data = array(
        'relationship_type_id' => EMPLOYER_RELATIONSHIP_TYPE_ID,
        'contact_id_a'         => $contact_id,
        'contact_id_b'         => $organisation_id,
        'is_active'            => 1);

      // add the department, if given
      if ($department) {
        $new_relationship_data['Kompi_Beziehung.Abteilung'] = $department;
      }

      CRM_Magento_CustomData::resolveCustomFields($new_relationship_data);
      civicrm_api3('Relationship', 'create', $new_relationship_data);
    }
  }
}
