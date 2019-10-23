<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a resource to get status of a source.
 *
 * @RestResource(
 *   id = "get_source_status",
 *   label = @Translation("Get the status of a Source"),
 *   uri_paths = {
 *     "canonical" = "/api/source/{sourceid}/status",
 *   }
 * )
 */
class GetSourceStatus extends ResourceBase {

  /**
   * Responds to GET requests for retrieving a status of a source.
   *
   * @param sourceid
   *   Unique ID of the source
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function get($sourceid = NULL) {

    // Array to display the source status.
    $source_info = [];
    $langcode = 'dv';

    $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_CONFIGURABLE);

    // Find out if the source of the given id exists
    // Query to find out if the source exists.
    $source_present = db_query("SELECT COUNT(*) FROM `heritage_source_info` WHERE  id = :sourceid", [':sourceid' => $sourceid])->fetchField();

    if ($source_present == 1) {

      // Select textid, textname of the given source.
      $textid = db_query("SELECT text_id FROM `heritage_source_info` WHERE id = :sourceid", [':sourceid' => $sourceid])->fetchField();

      $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

      // Original Content count present.
      $original_content_count = db_query("SELECT COUNT(*) FROM `node__field_original_content` WHERE bundle = :textname AND langcode = :langcode", [':textname' => $textname, ':langcode' => $langcode])->fetchField();

      // Load the source node.
      $node = Node::load($sourceid);

      // Query to find out the language of a source.
      $source_author = db_query("SELECT name FROM `taxonomy_term_field_data` WHERE tid = :authid", [':authid' => $node->field_author_name->target_id])->fetchField();

      // Query to find the biography of the author.
      $source_author_bio = db_query("SELECT field_bio_value FROM `taxonomy_term__field_bio` WHERE entity_id   = :authid", [':authid' => $node->field_author_name->target_id])->fetchField();

      $table_name = 'node__field_' . $textname . '_' . $sourceid . '_' . $node->field_format->value;

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

      $source_info['id'] = json_decode($sourceid, TRUE);
      $source_info['title'] = $node->title->value;
      $source_info['author_name'] = [
        'name' => $source_author,
        'id' => $node->field_author_name->target_id,
        'bio' => $source_author_bio,
      ];

      $source_info['original_content_count_present'] = $original_content_count;

      $source_info['languages'] = $sourcelang;
      $source_info['format'] = $node->field_format->value;
      $source_info['type'] = $node->field_type->value;
      $source_info['script'] = $node->field_scipt->value;
      $source_info['publisher_name'] = $node->field_publisher_name->value;

      // $meta_info = [

      //   // 'title' => $node->title->value,
      //   'alternative_titles' => $node->field_alternative_titles->value,

      //   'copyright_expiry_year' => $node->field_copyright_expiry_year->value,
      //   'copyright_license' => $node->field_copyright_license->value,
      //   'copyright_name' => $node->field_copyright_name->target_id,
      //   'copyright_year' => $node->field_copyright_name->value,
      //   'distribution_allowed' => $node->field_distribution_allowed->value,
      //   'download_allowed' => $node->field_download_allowed->value,
      //   'edition' => $node->field_edition->value,
      //   'foreword' => $node->field_foreword->value,

      //   'keywords' => $node->field_tags->target_id,

      //   'preface' => $node->field_preface->value,
      //   'prerequisites' => $node->field_prerequisites->value,
      //   'published_date' => json_decode($node->field_published_date->value, TRUE),

      //   'translated_titles' => $node->field_translated_titles->value,

      // ];

      $metadata = $_GET['metadata'];
      if (isset($_GET['metadata']) && $metadata == 1) {
        $meta_info = [

        // 'title' => $node->title->value,
        'alternative_titles' => $node->field_alternative_titles->value,

        'copyright_expiry_year' => $node->field_copyright_expiry_year->value,
        'copyright_license' => $node->field_copyright_license->value,
        'copyright_name' => $node->field_copyright_name->target_id,
        'copyright_year' => $node->field_copyright_name->value,
        'distribution_allowed' => $node->field_distribution_allowed->value,
        'download_allowed' => $node->field_download_allowed->value,
        'edition' => $node->field_edition->value,
        'foreword' => $node->field_foreword->value,

        'keywords' => $node->field_tags->target_id,

        'preface' => $node->field_preface->value,
        'prerequisites' => $node->field_prerequisites->value,
        'published_date' => json_decode($node->field_published_date->value, TRUE),

        'translated_titles' => $node->field_translated_titles->value,

      ];
        $source_info['metadata'] = $meta_info;

      }

      $message = $source_info;
      $statuscode = 200;

    }

    else {
      // Source of the given id does not exist.
      $message = [

        'success' => 0,
        'message' => 'source does not exist',
      ];

      $statuscode = 404;

    }

    return new ModifiedResourceResponse($message, $statuscode);

  }

}
