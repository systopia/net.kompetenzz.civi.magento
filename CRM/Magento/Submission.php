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

    // pass to XCM
    $contact_data['contact_type'] = $contact_type;
    $contact = civicrm_api3('Contact', 'getorcreate', $contact_data);
    if (empty($contact['id'])) {
      return NULL;
    } else {
      return $contact['id'];
    }
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
