<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Kohana_Controller_Rest - Extend this controller to make your controllers RESTful.
 *
 * @package Kohana-Restful-API
 * @version $id$
 * @author Brian Greenacare bgreenacre42@gmail.com
 */
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
     * payload - Contains the content to be serialized in the response.
     *
     * @var object
     * @access public
     */
    protected $_payload;

    /**
     * before 
     * 
     * @access public
     * @return void
     */
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
            // Parse the request body and put it into the post.
            // This is to make accessing request data more consistent.
            parse_str($this->request->body(), $_POST);
            $_POST = Kohana::sanitize($_POST);

            $this->request
                ->post($_POST);
        }

        if ( ! isset($this->_action_map[$this->_method]))
        {
            $this->response
                ->headers('Allow', implode(', ', array_keys($this->_action_map)));

            throw new HTTP_Exception_405('Method ":method" is not allowed.', array(
                ':method'   => $this->_method,
            ));
        }
    }

    /**
     * after 
     * 
     * @access public
     * @return void
     */
    public function after()
    {
        if (in_array($this->_method, array(HTTP_Request::PUT, HTTP_Request::DELETE, HTTP_Request::POST)))
        {
            // Do not cache any request that's is meant to change resources.
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

    /**
     * execute 
     * 
     * @access public
     * @return void
     */
    public function execute()
    {
        try
        {
            // Execute the "before action" method
            $this->before();

            // Override the action to the mapped one.
            $this->request->action($this->_action_map[$this->_method]);
            $action = 'action_'.$this->request->action();

            // If the action doesn't exist, it's a 404
            if ( ! method_exists($this, $action))
            {
                throw HTTP_Exception::factory(404,
                    'The requested URL :uri was not found on this server.',
                    array(':uri' => $this->request->uri())
                )->request($this->request);
            }

            // Execute the action itself
            $this->{$action}();

            // Execute the "after action" method
            $this->after();

            // Return the response
            return $this->response;
        }
        catch(HTTP_Exception $e)
        {
            $this->response->status($e->getCode());
            Payload::instance()
                ->code($e->getCode())
                ->errors($e->getMessage())
                ->render($this->request, $this->response);

            return $this->response;
        }
        catch(Kohana_Exception $e)
        {
            $this->response->status(500);
            Payload::instance()
                ->code(500)
                ->errors($e->getMessage())
                ->render($this->request, $this->response);

            return $this->response;
        }
    }

}
