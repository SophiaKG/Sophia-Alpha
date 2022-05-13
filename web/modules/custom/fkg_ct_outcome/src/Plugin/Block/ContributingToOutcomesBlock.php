<?php

/**
 * @file
 * Contains \Drupal\fkg_ct_outcome\Plugin\Block\<ContributingToOutcomesBlock.
 * 
 * @deprecated Alternation implemented in hook_preprocess_HOOK() implementation (for less admin interface involvement).
 */

namespace Drupal\fkg_ct_outcome\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;

/**
 * Provides an 'Outcomes this outcome contributing to' block.
 * 
 * @Block(
 *  id = "fkg_ct_outcome_contributing_to_outcomes",
 *  admin_label = @Translation("Contributing to other Outcomes"),
 *  category = @Translation("FKG"),
 *  context_definitions = {
 *    "node" = @ContextDefinition("entity:node", label = @Translation("Node")),
 *  },
 * )
 */
class ContributingToOutcomesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $outcome_owner_id = $node->get('field_bodies')->getString();
    $view_results = views_get_view_result('fkg_outcome_programs', 'owned_programs', $node->id(), $outcome_owner_id);

    $view_arg_programs_owned = implode('+',array_map(function ($result) {return $result->_entity->get('field_fkg_contrib_program')->getString() ?? NULL;}, $view_results));

    // Retrieve the outcomes that a given outcome's programs contribute to.
    // @see config/optional/views.view.fkg_outcome_programs.yml.
    $view = Views::getView('fkg_outcome_programs');
    $view->setDisplay('contributing_to_outcomes');
    $view->setArguments([$view_arg_programs_owned, $outcome_owner_id]);
    $view->preExecute();
    $view->execute();

    return $view->buildRenderable() ?? [];
  }
}
