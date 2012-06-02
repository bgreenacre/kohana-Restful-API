<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana_Payload_Format - Wrapper class for Payload Format classes.
 *
 * @package Kohana-Restful-API
 * @version $id$
 * @author Brian Greenacare bgreenacre42@gmail.com
 */
class Kohana_Payload_Format {

    public static function factory(Payload $payload)
    {
        $class = 'Payload_Format_'.ucfirst($payload->format());

        return new $class();
    }

}
