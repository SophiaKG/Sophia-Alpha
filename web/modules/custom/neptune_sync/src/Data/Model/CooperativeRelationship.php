<?php


namespace Drupal\neptune_sync\Data\Model;


use Drupal\neptune_sync\Data\DrupalEntityExport;
use Drupal\neptune_sync\Utility\SophiaGlobal;

class CooperativeRelationship extends Node implements DrupalEntityExport
{
    /** @var String Nid for entity reference */
    protected $owner;

    /** @var String text field 255 */
    protected $program;

    /** @var String text field long*/
    protected $programDesc;

    /** @var String text field 255*/
    protected $outcome;

    /** @var String text field long*/
    protected $outcomeDesc;

    /** @var String Nid for entity reference*/
    protected $receiver;

    /**
     * CooperativeRelationship constructor.
     */
    public function __construct(){
        //parent::__construct($title, $neptune_uri, SophiaGlobal::COOPERATIVE_RELATIONSHIP);
        $this->nodeType = SophiaGlobal::COOPERATIVE_RELATIONSHIP;
    }

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

    /**
     * @return String
     */
    public function getProgramDesc(): string
    {
        return $this->programDesc;
    }

    /**
     * @param String $programDesc
     */
    public function setProgramDesc(string $programDesc): void
    {
        $this->programDesc = $programDesc;
    }

    /**
     * @return String
     */
    public function getOutcomeDesc(): string
    {
        return $this->outcomeDesc;
    }

    /**
     * @param String $outcomeDesc
     */
    public function setOutcomeDesc(string $outcomeDesc): void
    {
        $this->outcomeDesc = $outcomeDesc;
    }

    public function getIDKey()
    {
        return $this->owner . '|' . $this->program . '|' . $this->outcome .
            '|' . $this->receiver; //pseudo hash
    }

    public function getEntityArray(){
        return array(
            'title' =>  $this->getIDKey(),
            'field_owner' => $this->owner,
            'field_program' => $this->program,
            'field_program_description_' => $this->programDesc,
            'field_outcome' => $this->outcome,
            'field_outcome_description' => $this->outcomeDesc,
            'field_receiver' => $this->receiver
        );
    }
}