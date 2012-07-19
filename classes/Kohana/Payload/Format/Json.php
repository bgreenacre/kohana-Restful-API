<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana_Payload_Format_Json - Json format class for Payload
 *
 * @package Kohana-Restful-API
 * @subpackage Payload
 * @version $id$
 * @author Brian Greenacare bgreenacre42@gmail.com
 */
class Kohana_Payload_Format_Json implements Payload_Format_Interface {

    protected $_types = array(
        'plain/text',
        'application/json',
    );

    public function render(Payload $payload, Request $request, Response $response)
    {
        $response->headers('Content-Type', $request->headers()->preferred_accept($this->_types))
            ->body(json_encode($payload->get()));
    }

}
