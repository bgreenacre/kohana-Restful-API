<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Controller_Rest extends Controller {

    /**
     * _action_map - Array of valid HTTP methods to actions
     *
     * @var array
     * @access protected
     */
    protected $_action_map = array(
        HTTP_Request::GET       => 'get',
        HTTP_Request::PUT       => 'update',
        HTTP_Request::POST      => 'create',
        HTTP_Request::DELETE    => 'delete',
    );

    /**
     * _method
     *
     * @var mixed
     * @access protected
     */
    protected $_method;

    /**
     * payload - Contains to serialize response.
     *
     * @var object
     * @access public
     */
    protected $_payload;

    public function before()
    {
        parent::before();

        // Get the singleton of the payload class.
        $this->_payload = Payload::instance();

        if ($this->request->is_initial())
        {
            // Set the type for the payload to the name of the controller.
            $this->_payload->type($this->request->controller());
        }

        // Get the method actually requested.
        $this->_method = Arr::get($_SERVER, 'HTTP_X_HTTP_METHOD_OVERRIDE', $this->request->method());

        if (HTTP_Request::PUT == $this->_method)
        {
            // Parse the request body and put it into the post
            parse_str($this->request->body(), $_POST);
            $_POST = Kohana::sanitize($_POST);

            $this->request
                ->post($_POST);
        }

        if ( ! isset($this->_action_map[$this->_method]))
        {
            $this->response
                ->headers('Allow', implode(', ', array_keys($this->_action_map)));

            throw new HTTP_Exception('Method ":method" is not allowed.', array(
                ':method'   => $this->_method,
            ), 405);
        }
        else
        {
            // Override the action to the mapped one.
            $this->request->action($this->_action_map[$this->_method]);
        }
    }

    public function after()
    {
        parent::after();

        if (in_array($this->_method, array(HTTP_Request::PUT, HTTP_Request::DELETE, HTTP_Request::POST)))
        {
            // Do not cache any request that's not a GET method.
            $this->response
                ->headers('cache-control', 'no-cache, no-store, max-age=0, must-revalidate');
        }

        if ($this->_method == HTTP_Request::POST AND ! $this->payload->errors())
        {
            $this->_payload
                ->code(201);

            $this->response
                ->status(201);
        }

        $this->_payload->render($this->request, $this->response);
    }

}
