<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana_Payload_Format_Interface - Interface for payload formater classes.
 *
 * @package Kohana-Restful-API
 * @version $id$
 * @author Brian Greenacare bgreenacre42@gmail.com
 */
interface Kohana_Payload_Format_Interface {

    public function render(Payload $payload, Request $request, Response $response);

}
