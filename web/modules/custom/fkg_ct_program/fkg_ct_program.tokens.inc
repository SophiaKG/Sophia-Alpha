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
      $node = Node::load($nid);

      $program_nid = $node->get('field_fkg_contrib_program')->getString();

      // Get program data
      $program_node = Node::load($program_nid);
      $program['title'] = $program_node->getTitle();
      $program['owner'] = $program_node->get('field_fkg_program_owner')->getString();
      $program['sequence'] = $program_node->get('field_fkg_program_number')->getString();
      $program['contribution'] = $node->get('field_fkg_contrib_description')->getString();

      $query_result = \Drupal::entityQuery('node')
        ->condition('type', 'bodies')
        ->condition('status', NodeInterface::PUBLISHED)
        ->condition('title', $program['owner'])
        ->execute();
      $owner_body_id = array_pop($query_result);
      if ($owner_body_id) {
        $program['owner_link'] = Node::load($owner_body_id)->toLink()->getUrl()->toString();
      }

      $programs[] = $program;
    }

    // Sort the programs according to 1) the owner and 2) the sequence number.
    usort($programs, function(array $a, array $b) {
      $owner_order = $a['owner'] <=> $b['owner'];

      return $owner_order != 0 ? $owner_order : strnatcmp($a['sequence'], $b['sequence']);
    });

    $outcomes[$node_id]['programs'] = $programs;
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
            <h5><b>Outcome #{{ outcome.sequence }}</b>: {{ outcome.description }}</h5>
            <div class="fkg-outcome-programs">
              {% if outcome.programs %}
                {% set owned_programs = outcome.programs|filter(v => v.owner == body) %}
                {% if owned_programs is not empty %}
                  {% set id_collapse_owned_program = collapse_outcome ~ "-owned-program" %}
                  <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#{{ id_collapse_owned_program }}" aria-expanded="false" aria-controls="{{ id_collapse_owned_program }}"><spam style="width: 25px; height: 25px; background-image:url(\'/themes/finance/images/icon-link-arrow-background.svg\'); background-size: contain; display:inline-block;margin-bottom: -5px; margin-right:10px;"></spam><spam>View programs</spam></button>
                  <div id="{{ id_collapse_owned_program }}" class="table-fkg-outcome-programs fkg-outcome-programs-owned collapse">
                    <table id="" class="table table-stripped">
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
                  </div>
                {% endif %}

                {% set contributed_programs = outcome.programs|filter(v => v.owner != body) %}
                {% if contributed_programs is not empty %}
                  {% set id_collapse_contributed_program = collapse_outcome ~ "-contributed-program" %}
                  <br /><br />
                  <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#{{ id_collapse_contributed_program }}" aria-expanded="false" aria-controls="{{ id_collapse_contributed_program }}"><spam style="width: 25px; height: 25px; background-image:url(\'/themes/finance/images/icon-link-arrow-background.svg\'); background-size: contain; display:inline-block;margin-bottom: -5px; margin-right:10px;"></spam><spam>View linked programs</spam></button>
                  <div id="{{ id_collapse_contributed_program }}" class="table-fkg-outcome-programs fkg-outcome-programs-contributed collapse" style="padding-left: 0px;">
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
                          <td>
                            {% if program.owner_link %}
                              <a href="{{ program.owner_link }}">
                            {{ program.owner }}</a>
                            {% else %}
                              {{ program.owner }}
                            {% endif %}
                          </td>
                          <td>{{ program.title }}</td>
                          <td>{{ program.contribution }}</td>
                        </tr>
                      {% endfor %}
                    </table>
                  </div>
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
