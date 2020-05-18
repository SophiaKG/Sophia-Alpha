<?php

namespace Drupal\neptune_sync\Graph;

/**
 * Class GraphFilters
 * @package Drupal\neptune_sync\Graph
 * @author Alexis Harper | DoF
 * A struct class for storing and passing filters of a graph to construct
 * @todo finish commenting
 */
class GraphFilters
{
    //XXX is this needed?
    public static $default_blacklist = ['class1', 'class2'];

    public $start_node;

    /**
     * @var integer
     * How many steps from the root node should the query travel down
     */
    public $steps;

    /**
     * @var array
     * An array of strings that list link names to not traverse down
     */
    public $blacklist_classes = [];

    public $blacklist_properties = [];

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

    /**
     * GraphFilters constructor.
     * @param array $form_array_filters
     *      based on form array found in neptune_sync\Form\LocalGraphForm::build_form()
     */
    public function __construct(array $form_array_filters){
        $this->start_node = $form_array_filters['node_title'];
        $this->steps = $form_array_filters['size_of_local_graph'];
        $this->blacklist_classes = $this->unpackTaxonomies($form_array_filters['ignore_results_that_are']);
        $this->blacklist_properties = $this->unpackTaxonomies($form_array_filters['ignore_relationship_types_of']);
        $this->only_relevant = 'N/A';
        $this->start_time = $form_array_filters['restrict_results_from_'];
        $this->end_time = $form_array_filters['restrict_results_to'];
    }

    private function unpackTaxonomies(array $terms){

        $term_names = [];
        foreach($terms as $term){
            $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term);
            array_push($term_names, $term->name->value);
        }
        return $term_names;
    }


}