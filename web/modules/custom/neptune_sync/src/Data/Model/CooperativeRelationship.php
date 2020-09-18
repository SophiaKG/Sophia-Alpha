<?php


namespace Drupal\neptune_sync\Data\Model;


use Drupal\neptune_sync\Data\DrupalEntityExport;

class CooperativeRelationship implements DrupalEntityExport
{
    /** @var String Nid for entity reference */
    protected $owner;

    /** @var String Nid for entity reference*/
    protected $program;

    /** @var String Nid for entity reference*/
    protected $outcome;

    /** @var String Nid for entity reference*/
    protected $receiver;

    /**
     * @return String
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * @param String $owner
     */
    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return String
     */
    public function getProgram(): string
    {
        return $this->program;
    }

    /**
     * @param String $program
     */
    public function setProgram(string $program): void
    {
        $this->program = $program;
    }

    /**
     * @return String
     */
    public function getOutcome(): string
    {
        return $this->outcome;
    }

    /**
     * @param String $outcome
     */
    public function setOutcome(string $outcome): void
    {
        $this->outcome = $outcome;
    }

    /**
     * @return String
     */
    public function getReceiver(): string
    {
        return $this->receiver;
    }

    /**
     * @param String $receiver
     */
    public function setReceiver(string $receiver): void
    {
        $this->receiver = $receiver;
    }

    public function getLabelKey()
    {
        return $this->owner . '|' . $this->program . '|' . $this->outcome .
            '|' . $this->receiver; //pseudo hash
    }

    public function getEntityType(){
        return 'node';
    }

    public function getSubType(){
        return 'cooperative_relationships';
    }

    public function getEntityArray(){
        return array(
            'title' =>  $this->getLabelKey(),
            'field_owner' => $this->owner,
            'field_program' => $this->program,
            'field_outcome' => $this->outcome,
            'field_receiver' => $this->receiver
        );
    }
}