<?php


namespace Drupal\neptune_sync\Utility;

use Drupal\neptune_sync\Data\DrupalEntityExport;

/**
 * Class Helper
 * @package Drupal\neptune_sync\Utility
 * @author Alexis Harper | DoF
 * A class fr debugging the module. To be removed on release
 */
class Helper
{
    private static $printMark = false;
    private static $markBuffer = "";

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

       $str .= Helper::print_var($input);

        file_put_contents('neptune_sync.log', $str, FILE_APPEND);
        if($event)
            file_put_contents('neptune_sync_event.log', $str, FILE_APPEND);
        if(self::$printMark)
            self::$markBuffer .= $str;

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

    public static function file_dump($filename, $input){

        $str = Helper::print_var($input);
        file_put_contents($filename, $str);
    }

    public static function print_entity(DrupalEntityExport $classModel, bool $fullDetails = false){

        $str = "Entity details:\n\t\t\t Label " . $classModel->getTitle() . " Type: " . $classModel->getSubType();
        if($fullDetails)
            $str .= Helper::print_var($classModel->getEntityArray());
        return $str;
    }

    public static function print_var($input){

        $str = "";
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
                foreach ($input as $key => $item){
                    $str .= "\n\t\t\tkey = [" . $key . "] val = ";
                    if(is_array($item)) {
                        $str .= "Array";
                        foreach ($item as $subKey => $subItem){
                            $str .= "\n\t\t\t\tkey = [" . $subKey . "] - val = ";
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
        return $str;
    }

    public static function setLogMark(){
        self::$printMark = true;
    }

    public static function getMarkStream(){
        self::$printMark = false;
        return self::$markBuffer;

    }
}