<?php

/**
 * @file
 */

use Drupal\Core\Render\BubbleableMetadata;

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
  $node_title = \Drupal\node\Entity\Node::load($nid)->getTitle();

  $query_result = \Drupal::entityQuery('node')
    ->condition('type', 'fkg_ct_outcomeauth')
    ->condition('title', $node_title)
    ->execute();
  $outcome_auth_nid = array_pop($query_result);

  if ($outcome_auth_nid) {
    $outcomes_authorized_nids = array_column( \Drupal\node\Entity\Node::load($outcome_auth_nid)->get('field_fkg_outcome')->getValue(), 'target_id');
  }
  else {
    return '';
  }

  $outcomes = [];
  foreach($outcomes_authorized_nids as $node_id) {
    $outcomes[$node_id]['description'] = array_column(\Drupal\node\Entity\Node::load($node_id)->get('field_description')->getValue(), 'value')[0];

    $query_result = \Drupal::entityQuery('node')
      ->condition('type', 'fkg_ct_program_contrib')
      ->condition('field_fkg_contrib_outcome', $node_id)
      ->execute();
    $contrib_program_nids = array_values($query_result);

    // Retrieve and group the data of programs.
    foreach ($contrib_program_nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $is_linked = array_column($node->get('field_fkg_contrib_linked')->getValue(), 'value')[0];

      $program_nid = $node->get('field_fkg_contrib_program')->getString();

      // Get program data
      $program_node = \Drupal\node\Entity\Node::load($program_nid);
      $program['title'] = $program_node->getTitle();
      $program['owner'] = $program_node->get('field_fkg_program_owner')->getString();
      $program['contribution'] = $node->get('field_fkg_contrib_description')->getString();

      $is_linked = $node->get('field_fkg_contrib_linked')->getString();
      $group_by_linked = $is_linked ? 'linked' : 'owned';
      $outcomes[$node_id]['programs'][$group_by_linked][] = $program;
    }

    // Render the program data.
    // @todo Switch to template-based rendering.
    $program_rendered = '<h2>Outcomes</h2>' . PHP_EOL;
    foreach ($outcomes as $outcome) {
      $program_rendered .= '<h3>' . $outcome['description'] . '</h3>';
      if ( !empty($outcome['programs']['owned']) ) {
        $program_rendered .= '<h4>Program(s)</h4>';
        $program_rendered .= '<table class="table">';
        $program_rendered .= '<tr><th>Program</th><th>Contribution</th></tr>';
        foreach ($outcome['programs']['owned'] as $owned_prog) {
          $program_rendered .= '<tr><td>' . $owned_prog['title'] . '</td>';
          $program_rendered .= '<td>' . $owned_prog['contribution'] . '</td></tr>';
        }
        $program_rendered .= '</table>';
      }
      if (!empty($outcome['programs']['linked'])) {
        $program_rendered .= '<h4>Linked Program(s)</h4>';
        $program_rendered .= '<table class="table">';
        $program_rendered .= '<tr><th>Program</th><th>Owner</th><th>Contribution</th></tr>';
        foreach ($outcome['programs']['linked'] as $linked_prog) {
          $program_rendered .= '<tr><td>' . $linked_prog['title'] . '</td>';
          $program_rendered .= '<td>' . $linked_prog['owner'] . '</td>';
          $program_rendered .= '<td>' . $linked_prog['contribution'] . '</td></tr>';
        }
        $program_rendered .= '</table>';
      }
    }
  }

  // Return as markup to prevent html tags being escaped.
  return \Drupal\Core\Render\Markup::create($program_rendered);
}
