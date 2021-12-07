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
    $outcome_node = \Drupal\node\Entity\Node::load($node_id);

    $outcomes[$node_id]['description'] = $outcome_node->get('field_description')->getString();
    $outcomes[$node_id]['sequence'] = $outcome_node->get('field_fkg_outcome_number')->getString();

    $query_result = \Drupal::entityQuery('node')
      ->condition('type', 'fkg_ct_program_contrib')
      ->condition('field_fkg_contrib_outcome', $node_id)
      ->execute();
    $contrib_program_nids = array_values($query_result);

    // Retrieve and group the data of programs.
    $programs = [];
    foreach ($contrib_program_nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);

      $program_nid = $node->get('field_fkg_contrib_program')->getString();

      // Get program data
      $program_node = \Drupal\node\Entity\Node::load($program_nid);
      $program['title'] = $program_node->getTitle();
      $program['owner'] = $program_node->get('field_fkg_program_owner')->getString();
      $program['sequence'] = $program_node->get('field_fkg_program_number')->getString();
      $program['contribution'] = $node->get('field_fkg_contrib_description')->getString();

      $programs[] = $program;
    }

    usort($programs, function(array $a, array $b){return $a['sequence'] <=> $b['sequence'];});
    usort($programs, function(array $a, array $b){return strnatcmp($a['owner'], $b['owner']);});

    $outcomes[$node_id]['programs'] = $programs;
  }

  // Construct the rendering data.
  $character_sheet = [
    '#type' => 'inline_template',
    '#template' => '
      <div class="body-programs">
        {% for outcome in outcomes|sort((a, b) => a.sequence <=> b.sequence) %}
          <div>
            <h5><b>Outcome #{{ outcome.sequence }}</b>: {{ outcome.description }}</h5>
            <div>
              {% if outcome.programs %}
                {% set owned_programs = outcome.programs|sort((a,b) => a.sequence <=> b.sequence)|filter(v => v.owner == body) %}
                {% if owned_programs is not empty %}
                  <h6>Programs</h6>
                  <table class="table table-stripped">
                    <tr>
                      <th>#</th>
                      <th>Program</th>
                      <th>Contribution</th>
                    </tr>
                    {% for program in owned_programs %}
                      <tr>
                        <td>{{ program.sequence }}</td>
                        <td>{{ program.title }}</td>
                        <td>{{ program.contribution }}</td>
                      </tr>
                    {% endfor %}
                  </table>
                {% endif %}

                {% set contributed_programs = outcome.programs|filter(v => v.owner != body) %}
                {% if contributed_programs is not empty %}
                  <h6>Linked programs</h6>
                  <table class="table table-stripped">
                    <tr>
                      <th>#</th>
                      <th>Owner</th>
                      <th>Program</th>
                      <th>Contribution</th>
                    </tr>
                    {% for program in contributed_programs %}
                      <tr>
                        <td>{{ program.sequence }}</td>
                        <td>{{ program.owner }}</td>
                        <td>{{ program.title }}</td>
                        <td>{{ program.contribution }}</td>
                      </tr>
                    {% endfor %}
                  </table>
                {% endif %}
              {% endif %}
            </div>
          </div>
        {% endfor %}
      </div>',
    '#context' => [
      'header' => $node_title,
      'outcomes' => $outcomes,
      'body' => $node_title,
    ],
  ];

  return \Drupal\Core\Render\Markup::create(\Drupal::service('renderer')->render($character_sheet));
}
