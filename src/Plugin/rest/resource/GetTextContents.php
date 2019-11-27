<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a resource to get status of a text.
 *
 * @RestResource(
 *   id = "get_text_contents",
 *   label = @Translation("Get the contents of Text"),
 *   uri_paths = {
 *     "canonical" = "/api/{textid}",
 *   }
 * )
 */
class GetTextContents extends ResourceBase {

  /**
   * Responds to GET requests for retrieving the contents of a text.
   *
   * @param textname
   *   Unique name of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function get($textid = NULL) {

    // Get the textid from textname.
    // $textid = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE field_machine_name_value = :textname", [':textname' => $textname])->fetchField();
    $textid = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

    // Set the default langcode.
    $langcode = 'dv';
    $contents = [];

    if (isset($textid) && $textid > 0) {
      $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

      // Entity id of the mool shloka.
      $moolid = db_query("SELECT id FROM `heritage_source_info` WHERE text_id = :textid AND type = :type", [':textid' => $textid , 'type' => 'moolam'])->fetchField();

      // If no position parameter is given.
      if (!isset($_GET['position'])) {
        // Find all the nodes of type == 'gita' with langcode = 'dv'.
        $available_texts = db_query("SELECT * FROM `node_field_data` WHERE type = :textname AND langcode = :langcode", [':textname' => $textname, ':langcode' => $langcode])->fetchAll();

        if (count($available_texts) > 0) {
          for ($i = 0; $i < count($available_texts); $i++) {
            $text_info = [];
            $mool_shloka = [];

            $flag = TRUE;
            $mool_shloka_flag = TRUE;
            $other_fields = [];

            $parameters = $_GET;
            // If fields are given.
            if (isset($_GET['language'])) {

              $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_CONFIGURABLE);

              $requested_language = ucfirst($_GET['language']);

              foreach ($languages as $language) {
                if ($requested_language == $language->getName()) {
                  $langcode = $language->getId();

                }
              }

            }
            foreach ($parameters as $key => $parameter) {
              $parameter_info = explode('_', $key);
              if ($parameter_info[0] == 'field') {

                if (count($parameter_info) >= 3) {
                  $mool_shloka_flag = FALSE;
                  $field = 'field_' . $parameter_info[1] . '_' . $parameter_info[2] . '_' . $parameter_info[3];

                  $field_name = 'field_' . $parameter_info[1] . '_' . $parameter_info[2] . '_' . $parameter_info[3] . '_value';

                  $table_name = 'node__field_' . $textname . '_' . $parameter_info[2] . '_' . $parameter_info[3];
                  // Query to find the language of the table.
                  if (doesBundleHaveField('node', $textname, $field) == TRUE) {
                    $table_lang = db_query("SELECT langcode FROM " . $table_name . " WHERE bundle = :textname", [':textname' => $textname])->fetchField();

                    if ($table_lang == 'en') {
                      // For the positional index find the corresponding english node.
                      $entityid_en = get_entityId($available_texts[$i]->title, $textname, 'en');

                      $field_content = db_query("SELECT $field_name FROM " . $table_name . " WHERE bundle = :textname AND entity_id = :entityid AND langcode = :langcode", [':textname' => $textname, ':entityid' => $entityid_en, ':langcode' => 'en'])->fetchField();
                      $other_fields['content'] = $field_content;

                      $text_info['nid'] = json_decode($available_texts[$i]->nid, TRUE);

                      $text_info['title'] = $available_texts[$i]->title;

                      // $text_info[$field] = $other_fields;
                    }

                    else {

                      $entityid_dv = get_entityId($available_texts[$i]->title, $textname, $langcode);

                      $field_content = db_query("SELECT $field_name FROM " . $table_name . " WHERE bundle = :textname AND entity_id = :entityid AND langcode = :langcode", [':textname' => $textname, ':entityid' => $entityid_dv, ':langcode' => $langcode])->fetchField();
                      $other_fields['content'] = $field_content;

                      $text_info['nid'] = json_decode($available_texts[$i]->nid, TRUE);

                      $text_info['title'] = $available_texts[$i]->title;

                      // $text_info[$field] = $other_fields;
                    }
                  }
                  else {
                    $flag = FALSE;
                  }
                  if ($flag == TRUE) {
                    $text_info[$field] = $other_fields;

                  }
                  else {
                    $message = [
                      'success' => 0,
                      'message' => "Field does not exist",

                    ];
                    $statuscode = 404;

                  }

                }

              }

            }
            if ($mool_shloka_flag == TRUE) {
              // When no parameters are given.
              $node = Node::load($available_texts[$i]->nid);
              $var = 'field_' . $textname . '_' . $moolid . '_text';
              // $mool_shloka['content'] = $node->field_gita_10589_text->value;
              $mool_shloka['content'] = $node->{$var}->value;

              // Collect the metadata for mool shloka.
              $metadata = $_GET['metadata'];
              if (isset($_GET['metadata']) && $metadata == 1) {
                // $var = 'field_' . $textname . '_' . $moolid . '_text';
                // $metadata_mool_sholka = collect_metadata($available_texts[$i]->nid, 'field_gita_10589_text', 'dv');
                $metadata_mool_sholka = collect_metadata($available_texts[$i]->nid, $var, 'dv');

                $mool_shloka['meta_data'] = json_decode($metadata_mool_sholka, TRUE);

              }

              $text_info['nid'] = json_decode($available_texts[$i]->nid, TRUE);
              $text_info['title'] = $available_texts[$i]->title;
              // $text_info['field_gita_10589_text'] = $mool_shloka;
              $text_info[$var] = $mool_shloka;

            }

            $contents[] = $text_info;

          }
          $message = $contents;
          $statuscode = 200;

        }

      }
      else {
        if (isset($_GET['position'])) {
          $flag = TRUE;
          $mool_shloka_flag = TRUE;
          $position = $_GET['position'];

          if (isset($_GET['language'])) {

            $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_CONFIGURABLE);

            $requested_language = ucfirst($_GET['language']);

            foreach ($languages as $language) {
              if ($requested_language == $language->getName()) {
                $langcode = $language->getId();

              }
            }

          }

          // If only position parameter is given, display the mool shloka and.
          $mool_shloka = [];
          $other_fields = [];

          $contents['title'] = $position;
          // $contents['nid'] = json_decode($entityid, TRUE);
          // $contents[] = $text_info;
          if ($langcode == 'en') {
            $entityid_en = get_entityId($position, $textname, $langcode);
            $contents['nid'] = json_decode($entityid_en, TRUE);
            // Display  mool shloka for that position.
            $entityid_mool = get_entityId($position, $textname, 'dv');
            $node_mool = Node::load($entityid_mool);
            $var = 'field_' . $textname . '_' . $moolid . '_text';
            // $mool_shloka['content'] = $node_mool->field_gita_10589_text->value;
            $mool_shloka['content'] = $node_mool->{$var}->value;

            $metadata = $_GET['metadata'];
            if (isset($_GET['metadata']) && $metadata == 1) {

              // $metadata_mool = collect_metadata($entityid_mool, 'field_gita_10589_text', 'dv');
              $metadata_mool = collect_metadata($entityid_mool, $var, 'dv');

              $mool_shloka['meta_data'] = json_decode($metadata_mool, TRUE);

            }

          }
          else {
            $entityid = get_entityId($position, $textname, $langcode);
            $contents['nid'] = json_decode($entityid, TRUE);
            $node = Node::load($entityid);
            // $mool_shloka['content'] = $node->field_gita_10589_text->value;
            $var = 'field_' . $textname . '_' . $moolid . '_text';
            $mool_shloka['content'] = $node->{$var}->value;

            $metadata = $_GET['metadata'];
            if (isset($_GET['metadata']) && $metadata == 1) {
              // $metadata_mool = collect_metadata($entityid, 'field_gita_10589_text', $langcode);
              $metadata_mool = collect_metadata($entityid, $var, $langcode);

              $mool_shloka['meta_data'] = json_decode($metadata_mool, TRUE);

            }

          }

          // $contents['field_gita_10589_text'] = $mool_shloka;
          // IF other parameter are given e.g field and language.
          $parameters = $_GET;

          foreach ($parameters as $key => $parameter) {
            $parameter_info = explode('_', $key);

            if ($parameter_info[0] == 'field') {
              if (count($parameter_info) >= 3) {

                $field = 'field_' . $parameter_info[1] . '_' . $parameter_info[2] . '_' . $parameter_info[3];
                $field_name = 'field_' . $parameter_info[1] . '_' . $parameter_info[2] . '_' . $parameter_info[3] . '_value';

                $table_name = 'node__field_' . $textname . '_' . $parameter_info[2] . '_' . $parameter_info[3];

                if (doesBundleHaveField('node', $textname, $field) == TRUE) {
                  // Get the content for the field from table.
                  $table_lang = db_query("SELECT langcode FROM " . $table_name . " WHERE bundle = :textname", [':textname' => $textname])->fetchField();
                  if (!isset($_GET['mool_shloka'])) {
                    $mool_shloka_flag = FALSE;

                  }

                  if ($table_lang == 'en') {
                    $entityid_en = get_entityId($position, $textname, 'en');

                    $field_content = db_query("SELECT $field_name FROM " . $table_name . " WHERE bundle = :textname AND entity_id = :entityid AND langcode = :langcode", [':textname' => $textname, ':entityid' => $entityid_en, ':langcode' => 'en'])->fetchField();

                    $other_fields['content'] = $field_content;
                    $metadata = $_GET['metadata'];
                    if (isset($_GET['metadata']) && $metadata == 1) {
                      $metadata_info = collect_metadata($entityid_en, $field, 'en');
                      $other_fields['metadata'] = json_decode($metadata_info, TRUE);

                    }

                    $contents[$field] = $other_fields;

                  }
                  else {
                    // $entityid_dv = get_entityId($position, $textname, 'dv');
                    $entityid_dv = get_entityId($position, $textname, $langcode);
                    // $field_content = db_query("SELECT $field_name FROM " . $table_name . " WHERE bundle = :textname AND entity_id = :entityid AND langcode = :langcode", [':textname' => $textname, ':entityid' => $entityid_dv, ':langcode' => 'dv'])->fetchField();
                    $field_content = db_query("SELECT $field_name FROM " . $table_name . " WHERE bundle = :textname AND entity_id = :entityid AND langcode = :langcode", [':textname' => $textname, ':entityid' => $entityid_dv, ':langcode' => $langcode])->fetchField();

                    $other_fields['content'] = $field_content;
                    $metadata = $_GET['metadata'];
                    if (isset($_GET['metadata']) && $metadata == 1) {
                      $metadata_info = collect_metadata($entityid_dv, $field, $langcode);
                      $other_fields['metadata'] = json_decode($metadata_info, TRUE);

                    }

                    $contents[$field] = $other_fields;

                  }

                }
                else {

                  $flag = FALSE;
                  // print_r('Given field does not exist');.
                }

              }

            }

          }
          if ($mool_shloka_flag == TRUE) {
            $var = 'field_' . $textname . '_' . $moolid . '_text';

            // $contents['field_gita_10589_text'] = $mool_shloka;
            $contents[$var] = $mool_shloka;

          }

          if ($flag == FALSE) {
            $message = [
              'success' => 0,
              'message' => "Field does not exist",

            ];
            $statuscode = 404;

          }
          else {

            $message = $contents;
            $statuscode = 200;
          }

        }
      }

    }

    else {
      $message = [
        'success' => 0,
        'message' => 'Text does not exist',
      ];

      $statuscode = 404;

    }

    return new ModifiedResourceResponse($message, $statuscode);

  }

}

/**
 *
 */
// Function collect_metadata($entityid, $field_name, $langcode) {
//   $metadata = db_query('SELECT metadata FROM `heritage_field_meta_data` WHERE nid = :entityid AND language = :langcode AND field_name = :field_name', [':entityid' => $entityid, ':langcode' => $langcode, ':field_name' => $field_name])->fetchField();
// return $metadata;
// }.
// /**
//  *
//  */
// function get_entityId($positional_index, $bundle, $langcode) {
//   $term_position = db_query('SELECT entity_id FROM `taxonomy_term__field_position` WHERE field_position_value = :position AND bundle = :bundle', [':position' => $positional_index, ':bundle' => $bundle])->fetchField();
// $entityid = db_query('SELECT entity_id FROM `node__field_positional_index` WHERE field_positional_index_target_id = :term_position AND langcode = :langcode', [':term_position' => $term_position, ':langcode' => $langcode])->fetchField();
// return $entityid;
// }
// /**
//  *
//  */
// function doesBundleHaveField($entity_type, $bundle, $field_name) {
//   $all_bundle_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
//   return isset($all_bundle_fields[$field_name]);
// }.
