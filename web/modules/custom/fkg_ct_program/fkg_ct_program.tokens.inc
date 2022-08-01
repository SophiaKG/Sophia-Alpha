<?php

/**
 * @file
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Implements hook_token_info().
 */
function fkg_ct_program_token_info() {
  $test = 'Hello';
  return [
    'types' => [
      'fkg_ct_program' => [
        'name' => t('FKG Programs'),
        'description' => t('Tokens related to FKG Outcomes, Programs and Contributions'),
      ],
    ],
    'tokens' => [
      'fkg_ct_program' => [
        'body_programs' => [
          'name' => t('Body related program'),
          'description' => t('Programs contribute to body authorized outcomes. Taking the node id of the body as a parameter.'),
        ],
      ],
    ]
  ];
}

/**
 * Implements hook_tokens().
 */
function fkg_ct_program_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'fkg_ct_program') {
    foreach ($tokens as $token => $original) {
      [$directive, $nid] = explode(':', $token);
      if ($directive == 'body_programs' && filter_var($nid, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== FALSE){
        $replacements[$original] = fkg_ct_program_get_body_programs($nid);
      }
    }
  }

  return $replacements;
}

/**
 * Retrieve the programs for specified body.
 *
 * @param integer $nid
 *   Node ID.
 * 
 * @return \Drupal\Core\Render\Markup instance || string.
 *   Markup instance with formatted string.
 */
function fkg_ct_program_get_body_programs($nid) {
  $node_title = Node::load($nid)->getTitle();

  $query_result = \Drupal::entityQuery('node')
    ->condition('type', 'fkg_ct_outcomeauth')
    ->condition('title', $node_title)
    ->execute();
  $outcome_auth_nid = array_pop($query_result);

  if ($outcome_auth_nid) {
    $outcomes_authorized_nids = array_column( Node::load($outcome_auth_nid)->get('field_fkg_outcome')->getValue(), 'target_id');
  }
  else {
    return '';
  }

  $outcomes = [];
  foreach($outcomes_authorized_nids as $node_id) {
    $outcome_node = Node::load($node_id);

    $outcomes[$node_id]['description'] = $outcome_node->get('field_fkg_description')->value;
    $outcomes[$node_id]['sequence'] = $outcome_node->get('field_fkg_numbering')->getString();
    $outcomes[$node_id]['url'] = $outcome_node->toUrl()->toString();

    $programs =  _fkg_ct_outcome_get_outcome_programs($outcome_node);
    $outcomes[$node_id]['programs'] = \Drupal\Core\Render\Markup::create(\Drupal::service('renderer')->render($programs));
  }

  // Construct the rendering data.
  $character_sheet = [
    '#type' => 'inline_template',
    '#template' => '
      <div class="body-programs">
        {% set outcome_collapse_id = 0 %}
        {% for outcome in outcomes|sort((a, b) => a.sequence <=> b.sequence) %}
          {% set collapse_outcome = "collapse-outcome-" ~ outcome_collapse_id %}
          {% set outcome_collapse_id = outcome_collapse_id + 1 %}
          <div class="fkg-character-sheet-outcome-collapsible">
            <h5><a href="{{ outcome.url }}"><b>Outcome #{{ outcome.sequence }}</b>: {{ outcome.description }}</a></h5>
            <div class="fkg-character-sheet-outcome-programs">
              {{ outcome.programs }}
            </div>
          </div>
        {% endfor %}
      </div>',
    '#context' => [
      'header' => $node_title,
      'outcomes' => $outcomes,
      'body' => $node_title,
    ],
    '#attached' => [
      'library' => ['fkg_display_charsheet/fkg_character_sheet'],
    ],
  ];

  return \Drupal\Core\Render\Markup::create(\Drupal::service('renderer')->render($character_sheet));
}
