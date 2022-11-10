<?php

/**
 * @file
 * Containes \Drupal\fkg_ct_outcome\Plugin\Block\GraphBlock.
 */

namespace Drupal\fkg_ct_outcome\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Html;

/**
 * Provides an 'Outcome Graph' block.
 * 
 * @Block(
 *  id = "fkg_ct_outcome_graph",
 * admin_label = @Translation("Outcome graph"),
 * category = @Translation("FKG"),
 * context_definitions = {
 *    "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *  },
 * )
 */
class GraphBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $view_results = views_get_view_result('fkg_outcome_programs', 'owned_programs', $node->id());

    // Construct the dot graph.
    $tooltip = Html::escape($node->get('field_fkg_description')->value);
    $graphviz = 'digraph { ';
    $graphviz .= 'node0 [label="' . $this->wrap($node->getTitle()) . '" tooltip="' . $tooltip . '"];';
    $i = 1;
    foreach ($view_results as $row) {
      $node_program = Node::load($row->_entity->get('field_fkg_program')->getString());
      $node_label = $node_program->getTitle();

      $program_owner = Node::load($node_program->get('field_bodies')->getString());
      $program_owner_text = ($program_owner && $program_owner->access()) ? $program_owner->getTitle() : '';

      $tooltip = Html::escape('Program owner:\n' . $program_owner_text . '\nContribution:\n' . $row->_entity->get('field_fkg_description')->value);

      $name = 'node' . $i;
      $graphviz .= $name . '[label="' . $this->wrap($node_label) . '" shape = "rectangle" fontsize="11" tooltip="' . $tooltip . '"];';
      $graphviz .= $name . ' -> node0 [splines = "true"];';
      $i++;
    }
    $graphviz .= '}'; 

    // Construct and return the renderable array with the dot graph data being passed to the js.
    // @see js/outcome-graph.js.
    return [
      '#type' => 'markup',
      '#markup' => Markup::create('
        <div id="outcome-graph"></div>'),
      '#attached' => [
        'library' => ['fkg_ct_outcome/outcome.dot-graph'],
        'drupalSettings' => [
          'fkg_graph' => $graphviz,
        ],
      ]
    ];
  }

  /**
   * Wrap long text to short multiple text.
   * 
   * @param string $label
   *   The string to be wrapped.
   * 
   * @return string
   *   The wrapped string.
   */
  private function wrap(String $label) {
    return wordwrap($label, 16, '\n', FALSE);
  }
}
