<?php

namespace Drupal\heritage_schema\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to add sources to a text.
 *
 * @RestResource(
 *   id = "add_source_info",
 *   label = @Translation("Add Sources to a Text"),
 *   uri_paths = {
 *     "canonical" = "/api/{textname}/add/sources",
	   "https://www.drupal.org/link-relations/create" = "/api/{textname}/add/sources"
 *   }
 * )
 */
class AddSourceNode extends ResourceBase {

  /**
   * Responds to POST requests for adding sources to a text.
   *
   * @param textid
   *   Unique ID of the text
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   */
	public function post($textname = NULL, $arg) {
		$in_correct = 0;
		$connection = \Drupal::database();
		$info_present = $connection->query("SELECT entity_id FROM `node__field_machine_name` WHERE field_machine_name_value = :textname AND bundle = 'heritage_text'", array(':textname' => $textname))->fetchField();
		if(isset($info_present) && $info_present > 0){
			$textid = $info_present;
			if(count($arg) == 0){
				$message = [
					'success' => 0,
					'message' => 'required parameters missing'
				];
				$statuscode = 400;
			}
			else{
				for($i=0; $i<count($arg); $i++){
					if(!isset($arg[$i]['title']) || !isset($arg[$i]['language']) || !isset($arg[$i]['author']) || !isset($arg[$i]['format']) || !isset($arg[$i]['type']) ){
						$message = [
							'success' => 0,
							'message' => 'required parameters missing'
						];
						$statuscode = 400;
						break;
					}
					else{
						if($arg[$i]['type'] != 'Translation' && $arg[$i]['type'] != 'Commentary' && $arg[$i]['type'] != 'Moolam'){
							$message = [
								'success' => 0,
								'message' => 'required parameters missing'
							];
							$statuscode = 400;
							break;
						}
						else {
							for($j=0; $j<count($arg[$i]['format']); $j++){
								if($arg[$i]['format'][$j] != 'Text' && $arg[$i]['format'][$j] != 'Audio' && $arg[$i]['format'][$j] != 'Video'){
									$in_correct = 1;
								}
							}
							if($in_correct == 1){
								$message = [
									'success' => 0,
									'message' => 'required parameters missing'
								];
								$statuscode = 400;
								break;
							}
							else{
								$arg[$i]['format'] =  array_unique($arg[$i]['format']);
								$result = _add_source_info($arg[$i], $textid);
								$message = [
									'success' => 1,
									'message' => 'source added'
								];
								$statuscode = 200;
							}
						}
					}
				}
			}
		}
		else{
			$message = [
				'success' => 0,
				'message' => 'page not found'
			];
			$statuscode = 404;
		}
		return new ModifiedResourceResponse($message, $statuscode);
	}	
}
