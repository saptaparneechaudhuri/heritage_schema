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
 *   id = "get_text_status",
 *   label = @Translation("Get the status of Text"),
 *   uri_paths = {
 *     "canonical" = "/api/text/{textid}/status",
 *   }
 * )
 */
class GetTextStatus extends ResourceBase {

  /**
   * Responds to GET requests for retrieving the status of a text.
   *
   * @param textid
   *   Unique ID of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function get($textid = NULL) {

    // Array to display as a response.
    $text_status = [];
    $langcode = 'dv';

    $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_CONFIGURABLE);

    // $textid = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE field_machine_name_value = :textname", [':textname' => $textname])->fetchField();
    $textid = db_query("SELECT entity_id FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

    // Original Content count present.
    // $original_content_count = db_query("SELECT COUNT(*) FROM `node__field_original_content` WHERE bundle = :textname AND langcode = :langcode", [':textname' => $textname, ':langcode' => $langcode])->fetchField();
    if (isset($textid) && $textid > 0) {
      $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();
      // Original Content count present.
      $original_content_count = db_query("SELECT COUNT(*) FROM `node__field_original_content` WHERE bundle = :textname AND langcode = :langcode", [':textname' => $textname, ':langcode' => $langcode])->fetchField();

      $text_status['id'] = json_decode($textid, TRUE);
      $text_status['machine_name'] = $textname;

      // Load the node of the given textid.
      $node = Node::load($textid);

      // Query to find the language of the text.
      $text_language = db_query("SELECT name FROM `taxonomy_term_field_data` WHERE tid = :langid", [':langid' => $node->field_language->target_id])->fetchField();

      // Query to find the author of the text.
      $text_author = db_query("SELECT name FROM `taxonomy_term_field_data` WHERE tid = :authid", [':authid' => $node->field_author_name->target_id])->fetchField();

      // Query to find the biography of the author.
      $text_author_bio = db_query("SELECT field_bio_value FROM `taxonomy_term__field_bio` WHERE entity_id	= :authid", [':authid' => $node->field_author_name->target_id])->fetchField();

      $text_status['title'] = $node->title->value;
      $text_status['author_name'] = [
        'name' => $text_author,
        'id' => $node->field_author_name->target_id,
        'bio' => $text_author_bio,
      ];
      $text_status['language'] = $text_language;
      $text_status['script'] = $node->field_scipt->value;
      $text_status['publisher_name'] = $node->field_publisher_name->value;
      $text_status['original_content_count_present'] = $original_content_count;

      // Query to find out the available sources for a text.
      $available_sources = db_query("SELECT * FROM `heritage_source_info` WHERE text_id = :textid ", [':textid' => $textid])->fetchAll();
      $sources = [];

      if (count($available_sources) > 0) {
        for ($i = 0; $i < count($available_sources); $i++) {

          $table_name = 'node__field_' . $textname . '_' . $available_sources[$i]->id . '_' . $available_sources[$i]->format;

          // Query to find out about the content present.
          $content_present_text = db_query("SELECT COUNT(*) FROM " . $table_name . " WHERE bundle = :textname", [':textname' => $textname])->fetchField();

          // Query to find out the language name of the source.
          $source_language = db_query("SELECT DISTINCT(langcode) FROM " . $table_name . " WHERE  bundle = :textname", [':textname' => $textname])->fetchAll();

          $sourcelang = [];

          foreach ($source_language as $key => $lang) {
            $content_present_lang = db_query("SELECT COUNT(*) FROM " . $table_name . " WHERE bundle = :textname AND langcode = :langcode", [':textname' => $textname, ':langcode' => $lang->langcode])->fetchField();

            // This loop is for printing the languages as English, Hindi, Bengali etc
            // Else it gets printed as en,dv,bn.
            foreach ($languages as $language) {
              if ($lang->langcode == $language->getId()) {
                $langcode = $language->getName();
              }
            }

            $sourcelang[] = [
              'name' => $langcode,
              'total_content_present' => $content_present_lang,
            ];

          }

          // Query to find out the name of the author for a given source.
          $source_author = db_query("SELECT name FROM `taxonomy_term_field_data` WHERE tid = :authid", [':authid' => $available_sources[$i]->author])->fetchField();

          $source_info = [];

          $source_info['id'] = json_decode($available_sources[$i]->id, TRUE);
          $source_info['title'] = $available_sources[$i]->title;
          $source_info['author'] = $source_author;
          $source_info['total_number_of_content_present'] = $content_present_text;

          $source_info['language'] = $sourcelang;
          $source_info['format'] = $available_sources[$i]->format;
          $source_info['type'] = $available_sources[$i]->type;

          $sources[] = $source_info;
        }

        $text_status['total_number_of_sources'] = count($available_sources);
        $text_status['sources'] = $sources;

      }

      $message = $text_status;
      $statuscode = 200;

    }

    // If textid does not exist.
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
