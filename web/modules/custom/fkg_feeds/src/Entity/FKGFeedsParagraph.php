<?php

namespace Drupal\fkg_feeds\Entity;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Extend the Paragraph class to add postSave operations.
 * 
 * @see fkg_feeds_entity_type_alter().
 */
class FKGFeedsParagraph extends Paragraph {
  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Attach the generated paragraph to the parent entity.
    // @todo Update the conditions (such as test feeds import).
    if ($this->getType() === 'fkg_report_requirement') {
      $para_id = $this->id();
      $para_vid = $this->getRevisionId();
      $parent_field_name = $this->get('parent_field_name')->getString();

      if ($parent_entity = $this->getParentEntity() ) {
        $parent_entity->set(
          $parent_field_name,
          [
            [
              'target_id' => $para_id,
              'target_revision_id' => $para_vid
            ]
          ]
        );
        $parent_entity->save();
      }
    }
  }
}
