<?php

namespace Drupal\neptune_sync\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\neptune_sync\Graph\GraphGenerator;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LocalGraphForm
 * @package Drupal\neptune_sync\Form
 * @author Alexis Harper | DoF
 * This class is a form for building and displaying a filter selection form to
 * tailor how a local graph will be generated. It will accept these filters,
 * call the generator and redirect to the generated results.
 */
class LocalGraphForm extends FormBase {

    public function getFormId(){
        return 'local_graph_form';
    }

    /**
     *  This form is mostly copied and pasted from a copy built in webform, copied
     * the devel api output
     * and pasted into the render array
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * @param NodeInterface|null $node
     * @return array
     *      The form render array
     */
    public function buildForm(array $form, FormStateInterface $form_state,
                              NodeInterface $node = null){

        $form['markup'] = [
            '#type' => 'webform_markup',
            '#markup' => t("Query starting at @nodetitle",
                array('@nodetitle' => $node->getTitle())),
        ];

        $form['node_title'] = [
            '#type' => 'hidden',
            '#value' => $node->getTitle(),
        ];

        $form['size_of_local_graph'] = [
            '#type' => 'select',
            '#title' => $this->t('Size of local graph'),
            '#help' => $this->t('The number of links that should be explored from' .
                ' the selected label'),
            '#options' => [
                '1' => $this->t('1'),
                '2' => $this->t('2'),
                '3' => $this->t('3'),
                '4' => $this->t('4'),
                '5' => $this->t('5'),
            ],
            '#required' => TRUE,
        ];
        $form['ignore_results_that_are'] = [
            '#type' => 'webform_entity_select',
            '#title' => $this->t('Ignore results that are'),
            '#multiple' => TRUE,
            '#help' => $this->t('Do not explore nodes that are of the selected type/s'),
            '#target_type' => 'taxonomy_term',
            '#selection_handler' => 'default:taxonomy_term',
            '#selection_settings' => [
                'target_bundles' => [
                    'rdf_class_type' => 'rdf_class_type',
                ],
                'sort' => [
                    'field' => 'name',
                    'direction' => 'ASC',
                ],
            ],
            //TODO add this later '#default_value' => $config->get('ignore_results_that_are'),
        ];
        $form['ignore_relationship_types_of'] = [
            '#type' => 'webform_entity_select',
            '#title' => $this->t('Ignore relationship types of'),
            '#multiple' => TRUE,
            '#help' => $this->t('Do not explore down the provided relationship types'),
            '#target_type' => 'taxonomy_term',
            '#selection_handler' => 'default:taxonomy_term',
            '#selection_settings' => [
                'target_bundles' => [
                    'rdf_relationships' => 'rdf_relationships',
                ],
                'sort' => [
                    'field' => 'name',
                    'direction' => 'ASC',
                ],
            ],
            //TODO add this later '#default_value' => $config->get('ignore_relationship_types_of'),
        ];
        //TODO fix date default values and min/max values
        $form['restrict_results_from_'] = [
            '#type' => 'date',
            '#title' => $this->t('Restrict results from '),
            '#help' => $this->t('Provide the start of a range that results must fall 
            under if they have a date attached. This field is optional '),
            '#default_value' => array(
                'day' => 15,
                'month' => 01,
                'year' => 1901,
            ),
            //'#date_date_min' => '01-01-1901',
            '#datepicker' => TRUE,
            '#datepicker_button' => TRUE,
        ];
        $form['restrict_results_to'] = [
            '#type' => 'date',
            '#title' => $this->t('Restrict results to'),
            '#help' => $this->t('Provide the end of a range that results must fall under ' .
                ' if they have a date attached. This field is optional '),
            //'#default_value' => 'Today',
            //'#date_date_max' => 'Today',
            '#datepicker' => TRUE,
            '#datepicker_button' => TRUE,
        ];


        $form['actions'] = [
            '#type' => 'actions',
            '#tree' => TRUE,
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => 'Run query',
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * @todo this could use some work in a future sprint
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state){
        parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub
    }

    /**
     * Build graph and returns the graph id to  controller for displaying
     * Graph generator -> Query builder -> Query manager -> submitForm -> controller
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function  submitForm(array &$form, FormStateInterface $form_state){

        $filters = $form_state->getValues();
        $graphGen = new GraphGenerator();
        /*$graph_path = */$graphGen->buildGraphFromFilters($filters);

        //extract graph id from the built graph path XXX this might be done better in the future?
        //$graph_id = substr($graph_path, strripos($graph_path, '/') + 1);

        //call simple route and redirect to it
        $path = \Drupal\Core\Url::fromRoute('neptune_sync.testTemplate',
            ['nodename' => $filters['node_title']])->toString();
        $response = new RedirectResponse($path);
        $response->send();
    }
}