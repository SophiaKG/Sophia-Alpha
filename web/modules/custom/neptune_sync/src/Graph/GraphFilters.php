<?php

namespace Drupal\neptune_sync\Graph;

/**
 * Class GraphFilters
 * @package Drupal\neptune_sync\Graph
 * @author Alexis Harper | DoF
 * A struct class for storing and passing filters of a graph to construct
 */
class GraphFilters
{
    public static $default_blacklist = ['class1', 'class2'];

    /**
     * @var integer
     * How many steps from the root node should the query travel down
     */
    public $steps;

    /**
     * @var array
     * An array of strings that list link names to not traverse down
     */
    public $blacklist = [];

    /**
     * @var boolean
     * If true, only show the "active" version of a node that has multiple version
     */
    public $only_relevant;

    /**
     * @var
     * @todo find good date container
     */
    public $start_time;

    /**
     * @var
     * @todo find good date container
     */
    public $end_time;
}