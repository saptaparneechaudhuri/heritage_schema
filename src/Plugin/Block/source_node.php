<?php
/**
 * @file
 * Contains \Drupal\heritage_schema\Plugin\Block\sourcenode
 */

namespace Drupal\heritage_schema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;

/**
 * Provides a block to Display available Sources and to add a Source node to a heritage text.
 *
 * @Block(
 *   id = "source_node",
 *   admin_label = @Translation("Display Available Sources and add a source node to a heritage text"),
 *   category = @Translation("Custom")
 * )
 */
class source_node extends BlockBase {
	/**
	 * {@inheritdoc}
	 */
	public function build() {
		$build = [];
		$sources = '';

		//create the link to add a new source
		$attributes = [
						'class' => ['use-ajax'],
						'data-dialog-type' => 'dialog',
						'data-dialog-renderer' => 'off_canvas',
						'data-dialog-options' => Json::encode([
							'width' => 700,
						]),
					];
		$path = \Drupal::request()->getpathInfo();
                print_r($path);
		$arg  = explode('/',$path);
		$textId = $arg[2];
		//$url = Url::fromRoute('heritage_schema.addsource', array('node' => $textId), array('attributes' => $attributes));
               // $url = Url::fromRoute('node.add', ['node_type' => 'source_node'], ['attributes' => $attributes]);
               // setting up a normal link instead of using ajax
                $url = Url::fromRoute('node.add', ['node_type' => 'source_node']);
                $url->setOption('query',[
		 	'textid' => $textId,
		 ]);
                //print_r($url);
		$source_node_add_link = \Drupal::l(t('Add a Source Node'), $url);

		//get machine name of the text
		$text_name = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textId", array(':textId' => $textId))->fetchField();

		//fetch all the available sources
		$available_sources = db_query("SELECT * FROM `heritage_source_info` WHERE text_id = :textid ORDER BY language DESC", array(':textid' => $textId))->fetchAll();
		if(count($available_sources) > 0){
			for($i = 0; $i<count($available_sources); $i++){
				$table_name = 'node__field_'.$text_name.'_'.$available_sources[$i]->id.'_text';
				$filed_name = 'field_'.$text_name.'_'.$available_sources[$i]->id.'_text_value';
				//$langcode = 'dv';
				$content_present = db_query("SELECT COUNT(*) FROM ".$table_name." WHERE bundle = :text_name", array(':text_name' => $text_name))->fetchField();
				$sources = $sources.$available_sources[$i]->title.'</br>(<small><i>Content Present: '.$content_present.'</i></small>)</br>';
			}
		}
		$render = $sources.$source_node_add_link;
		$build['#markup'] = render($render);
		$build['#cache']['max-age'] = 0;
		return $build;
	}
}

