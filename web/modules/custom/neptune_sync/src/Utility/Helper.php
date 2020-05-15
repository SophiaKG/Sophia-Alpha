<?php


namespace Drupal\neptune_sync\Utility;

/**
 * Class Helper
 * @package Drupal\neptune_sync\Utility
 * @author Alexis Harper | DoF
 * A class fr debugging the module. To be removed on release
 */
class Helper
{

    /**
     *
     * TODO: add better argument display ie:   $this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
     * @param string $str
     * @param mixed ...$args
     */
    public static function log(string $str, ...$args){
        $date = new \DateTime();
        $date_str = $date->format('Y-m-d H:i:s');
        $str = $date_str . '| log: ' . $str . "\n";
        foreach($args as $a){
            $str .+ (string)$a . "\n";
        }
        file_put_contents('neptune_sync.log', $str, FILE_APPEND);
    }
}