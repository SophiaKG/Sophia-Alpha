<?php


namespace Drupal\neptune_sync\Data;


class CooperativeRelationship implements DrupalEntityExport
{
    /** @var integer */
    protected $owner;

    /** @var integer */
    protected $program;

    /** @var integer */
    protected $outcome;

    /** @var integer */
    protected $receiver;

    /**
     * @return int
     */
    public function getOwner(): int
    {
        return $this->owner;
    }

    /**
     * @param int $owner
     */
    public function setOwner(int $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return int
     */
    public function getProgram(): int
    {
        return $this->program;
    }

    /**
     * @param int $program
     */
    public function setProgram(int $program): void
    {
        $this->program = $program;
    }

    /**
     * @return int
     */
    public function getOutcome(): int
    {
        return $this->outcome;
    }

    /**
     * @param int $outcome
     */
    public function setOutcome(int $outcome): void
    {
        $this->outcome = $outcome;
    }

    /**
     * @return int
     */
    public function getReceiver(): int
    {
        return $this->receiver;
    }

    /**
     * @param int $receiver
     */
    public function setReceiver(int $receiver): void
    {
        $this->receiver = $receiver;
    }

    public function getEntityType(){
        return 'node';
    }

    public function getSubType(){
        return 'cooperative_relationships';
    }

    public function getEntityArray(){
        return array(
            'title' =>  $this->owner . " to " . $this->receiver,
            'field_owner' => $this->owner,
            'field_program' => $this->program,
            'field_outcome' => $this->outcome,
            'field_receiver' => $this->receiver
        );
    }
}