<?php

namespace Drupal\neptune_sync\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\neptune_sync\Utility\Helper;

class LocalGraphForm extends FormBase {


    public function getFormId()
    {
        return 'local_graph_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $x =\Drupal\Webform\Entity\Webform::load('local_grapgh_query');
        //kint($x, 'pre getSubmissionForm');
        $y = \Drupal::entityManager()
            ->getViewBuilder('webform')
            ->view($x);
        //kint($y, 'form view ');


       /* $form['elements'] = $y['elements'];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];
        kint($form);*/

        $form['size_of_local_graph'] = [
            '#type' => 'select',
            '#title' => $this->t('Size of local graph'),
            '#help' => $this->t('The number of links that should be explored from the selected label'),
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
        $form['restrict_results_from_'] = [
            '#type' => 'date',
            '#title' => $this->t('Restrict results from '),
            '#help' => $this->t('Provide the start of a range that results must fall under if they have a date attached. This field is optional '),
            '#default_value' => array(
                'year' => 1901,
                'month' => 1,
                'day' => 15,
            ),
            '#date_date_min' => '01-01-1901',
            '#datepicker' => TRUE,
            '#datepicker_button' => TRUE,
        ];
        $form['restrict_results_to'] = [
            '#type' => 'date',
            '#title' => $this->t('Restrict results to'),
            '#help' => $this->t('Provide the end of a range that results must fall under if they have a date attached. This field is optional '),
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

        // Process elements.
        //$this->elementManager->processElements($form);

        // Replace tokens.
       // $form = $this->tokenManager->replace($form);

       /* // Attach the webform library.
        $form['#attached']['library'][] = 'webform/webform.form';

        // Autofocus: Save details open/close state.
        $form['#attributes']['class'][] = 'js-webform-autofocus';
        $form['#attached']['library'][] = 'webform/webform.form.auto_focus';

        // Unsaved: Warn users about unsaved changes.
        $form['#attributes']['class'][] = 'js-webform-unsaved';
        $form['#attached']['library'][] = 'webform/webform.form.unsaved';

        // Details save: Attach details element save open/close library.
        $form['#attached']['library'][] = 'webform/webform.element.details.save';

        // Details toggle: Display collapse/expand all details link.
        $form['#attributes']['class'][] = 'js-webform-details-toggle';
        $form['#attributes']['class'][] = 'webform-details-toggle';
        $form['#attached']['library'][] = 'webform/webform.element.details.toggle';*/


        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub
    }

    public function  submitForm(array &$form, FormStateInterface $form_state){
        kint($form_state);
        kint($form_state->getValues());
        $this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValues()]));
    }
}