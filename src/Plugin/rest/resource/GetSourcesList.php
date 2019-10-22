<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a resource to add sources to a text.
 *
 * @RestResource(
 *   id = "get_sources_list",
 *   label = @Translation("Get the list of Sources"),
 *   uri_paths = {
 *     "canonical" = "/api/{textid}/sources",
 *   }
 * )
 */
class GetSourcesList extends ResourceBase {

  /**
   * Responds to GET requests for retrieving the list of sources of a text.
   *
   * @param textid
   *   Unique ID of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function get($textid = NULL) {
    $connection = \Drupal::database();
    $sources = [];
    $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    $textid = $connection->query("SELECT entity_id FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

    if (isset($textid) && $textid > 0) {
      $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();
      $available_sources = db_query("SELECT * FROM `heritage_source_info` WHERE text_id = :textid ORDER BY language DESC", [':textid' => $textid])->fetchAll();
      if (count($available_sources) > 0) {
        for ($i = 0; $i < count($available_sources); $i++) {
          $source_info = [];
          $source_info['id'] = json_decode($available_sources[$i]->id, TRUE);
          $source_info['title'] = $available_sources[$i]->title;
          // Query to find out the language of a source.
          $source_author = db_query("SELECT name FROM `taxonomy_term_field_data` WHERE tid = :authid", [':authid' => $available_sources[$i]->author])->fetchField();
          $source_info['author'] = $source_author;
          // Query to find the language of the text.
          $source_language = db_query("SELECT name FROM `taxonomy_term_field_data` WHERE tid = :langid", [':langid' => $available_sources[$i]->language])->fetchField();

          $source_info['language'] = $source_language;
          $source_info['format'] = $available_sources[$i]->format;
          $source_info['type'] = $available_sources[$i]->type;
          $source_info['field_name'] = 'field_' . $textname . '_' . $source_info['id'];
          $sources[] = $source_info;
        }
      }
      $message = $sources;
      $statuscode = 200;
    }
    else {
      $message = [
        'success' => 0,
        'message' => 'page not found',
      ];
      $statuscode = 404;
    }
    return new ModifiedResourceResponse($message, $statuscode);
  }

}
