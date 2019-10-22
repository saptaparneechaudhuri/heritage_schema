<?php

namespace Drupal\heritage_schema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Provides a block to Display available Sources, .
 *
 * And to add a Source node to a heritage text.
 *
 * @Block(
 *   id = "source_node",
 *   admin_label = @Translation("Display Available Sources and add a source node to a heritage text"),
 *   category = @Translation("Custom")
 * )
 */
class SourceNode extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */

  protected $currPath;

  /**
   * The link generator service.
   *
   * @var pathLink\Drupal\Core\Utility\LinkGeneratorInterface
   */


  protected $pathLink;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPathStack $currPath, LinkGeneratorInterface $pathLink) {

    parent:: __construct($configuration, $plugin_id, $plugin_definition);
    $this->currPath = $currPath;
    $this->pathLink = $pathLink;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(

      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $sources = '';
    //$link = '';

    // Create the link to add a new source.
    $attributes = [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'dialog',
      'data-dialog-renderer' => 'off_canvas',
      'data-dialog-options' => Json::encode([
        'width' => 700,
      ]),
    ];
    // $path = \Drupal::request()->getpathInfo();
    // use dependency injection to find path
    $path = $this->currPath->getPath();
    //print_r($path);
    $arg = explode('/', $path);
    $textId = $arg[2];
    // $url = Url::fromRoute('heritage_schema.addsource', array('node' => $textId), array('attributes' => $attributes));
    // $url = Url::fromRoute('node.add', ['node_type' => 'source_node'], ['attributes' => $attributes]);
    // setting up a normal link instead of using ajax
    $url = Url::fromRoute('node.add', ['node_type' => 'source_node']);
    $url->setOption('query', [
      'textid' => $textId,
    ]);
    // print_r($url);
    // $source_node_add_link = \Drupal::l(t('Add a Source Node'), $url);.
    $source_node_add_link = $this->pathLink->generate('Add a Source Node', $url);

    // Get machine name of the text.
    $text_name = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textId", [':textId' => $textId])->fetchField();

    // Fetch all the available sources.
    $available_sources = db_query("SELECT * FROM `heritage_source_info` WHERE text_id = :textid ORDER BY language DESC", [':textid' => $textId])->fetchAll();

    if (count($available_sources) > 0) {
      for ($i = 0; $i < count($available_sources); $i++) {
        // print_r($available_sources[$i]->format);
        // echo "</br>";.
        if ($available_sources[$i]->format == 'text') {
          $table_name = 'node__field_' . $text_name . '_' . $available_sources[$i]->id . '_text';
          $filed_name = 'field_' . $text_name . '_' . $available_sources[$i]->id . '_text_value';

        }
        else {
          $table_name = 'node__field_' . $text_name . '_' . $available_sources[$i]->id . '_' . $available_sources[$i]->format;
          // Look at structure for the audio field
          // The file name is referred to as target_id.
          $filed_name = 'field_' . $text_name . '_' . $available_sources[$i]->id . '_' . $available_sources[$i]->format . '_target_id';

        }
        // $table_name = 'node__field_' . $text_name . '_' . $available_sources[$i]->id . '_text';
        // $filed_name = 'field_' . $text_name . '_' . $available_sources[$i]->id . '_text_value';
        // $langcode = 'dv';
        $content_present = db_query("SELECT COUNT(*) FROM " . $table_name . " WHERE bundle = :text_name", [':text_name' => $text_name])->fetchField();
        $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $available_sources[$i]->id]);
         $link = $this->pathLink->generate($available_sources[$i]->title, $url);
       // $sources = $sources . $available_sources[$i]->title . '</br>(<small><i>Content Present: ' . $content_present . '</i></small>)</br>';
        $sources = $sources . $link . '</br>(<small><i>Content Present: ' . $content_present . '</i></small>)</br>';
       
        
      }
    }
    //$render = $sources . $source_node_add_link;
    $render = $sources . $source_node_add_link;
    $build['#markup'] = render($render);
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
