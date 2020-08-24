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
     * @param $input
     * @param bool $event
     * @param mixed ...$args
     */
    public static function log($input, bool $event = false, ...$args){
        $date = new \DateTime();
        $date_str = $date->format('Y-m-d H:i:s');
        $str ="\n" . $date_str . '| log: ';

        switch (gettype($input)) {
            case "boolean":
                if($input)
                    $str .= ' true';
                else
                    $str .= ' false';
                break;
            case "integer":
            case "double":
            case "string":
                $str .=  strval($input);
                break;
            case "array":
                $str .= "Array: ";
                foreach ($input as $item){
                    $str .= "\n\t\t\tkey = " . key($item) . "val = ";
                    if(is_array($item)) {
                        $str .= "Array";
                        foreach ($item as $subItem){
                            $str .= "\n\t\t\t\tkey = " . key($subItem) . "val = ";
                            if(is_array($subItem))
                                $str .= "array (too deep to expend)\n\t\t\t\t";
                            else
                                $str .= $subItem;
                        }
                    } else
                        $str .= $item;
                }
                break;
            default:
                $str .= " unable to print out " . gettype($input) . " " . get_class($input);
                break;
        }

        file_put_contents('neptune_sync.log', $str, FILE_APPEND);
        if($event)
            file_put_contents('neptune_sync_event.log', $str, FILE_APPEND);

        foreach ($args as $k)
            self::log($k, $event);
    }

    public static function var_dump($var, $text = null){
        if($text != null)
            Helper::log($text);

        ob_flush();
        ob_start();
        self::var_dump($var);
        Helper::log(ob_get_flush());
    }
}