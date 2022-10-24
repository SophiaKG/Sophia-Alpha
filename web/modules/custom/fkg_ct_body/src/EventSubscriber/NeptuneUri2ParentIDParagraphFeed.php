<?php

namespace Drupal\fkg_ct_body\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * Convert neptune URI to node id for paragraph feeds.
 */
class NeptuneUri2ParentIdParagraphFeed extends AfterParseBase {
  /**
   * {@inheritdoc}
   */
  public function applies(ParseEvent $event) {
    return $event->getFeed()->getType()->id() === 'fkg_report_requirement';
  }

  /**
   * {@inheritdoc}
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {
    $neptune_uri = $item->get('body');

    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'bodies')
      ->condition('field_neptune_uri', $neptune_uri)
      ->execute();
    
    // Replace the neptune uri with node id, skip if not found.
    $body_id = array_pop($nids);
    if ($body_id) {
      $item->set('body', $body_id);
    }
    else {
      throw new \Drupal\feeds\Exception\SkipItemException('No parent entity found for the given (Body) neptune_uri value.');
    }
  }
}
