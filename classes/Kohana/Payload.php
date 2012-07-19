<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana_Payload - Provides a singleton object to be used
 * with HMVC reqeusts in order to build a serialized result.
 *
 * @package Kohana-Restful-API
 * @subpackage Payload
 * @version $id$
 * @author Brian Greenacare bgreenacre42@gmail.com
 */
class Kohana_Payload implements ArrayAccess, Serializable {

    /**
     * @static
     * @access protected
     * @var object
     */
    protected static $_instance;

    /**
     * @static
     * @access protected
     * @var array
     */
    protected static $_accepted_types = array(
        'json'  => array(
            'plain/text',
            'application/json',
        ),
        'xml'   => array(
            'text/xhtml',
            'text/xml',
            'application/xml',
        ),
        'jsonp' => array(
            'application/javascript',
        ),
    );

    /**
     * instance - Instantiate and return singleton.
     *
     * @static
     * @access public
     * @return void
     */
    public static function instance()
    {
        if ( ! is_object(Payload::$_instance))
        {
            Payload::$_instance = new Payload();
        }

        return Payload::$_instance;
    }

    /**
     * @var mixed
     * @access protected
     */
    protected $_format = 'json';

    /**
     * @var float
     * @access protected
     */
    protected $_code = 200;

    /**
     * @var mixed
     * @access protected
     */
    protected $_errors;

    /**
     * @var mixed
     * @access protected
     */
    protected $_debug = array();

    /**
     * @var mixed
     * @access protected
     */
    protected $_type;

    /**
     * @var array
     * @access protected
     */
    protected $_meta = array();

    /**
     * @var array
     * @access protected
     */
    protected $_data = array();

    /**
     * __construct - Initialize object.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->_init();
    }

    /**
     * determine_format - Attempt to figure out the right
     * Payload_Format class to use based on the preferred_accept
     * header on the Request object.
     *
     * @access public
     * @return void
     */
    public function determine_format()
    {
        foreach (Payload::$_accepted_types as $format => $types)
        {
            $mime = Request::initial()->headers()
                ->preferred_accept($types);

            if ($mime !== FALSE)
            {
                $this->format($format);

                return $this;
            }
        }

        throw new HTTP_Exception('Supplied accept mimes: :accept not supported. Supported mimes: :mimes', array(
            ':accept'   => Request::initial()->headers('Accept'),
            ':mimes'    => implode(', ', Arr::flatten(Payload::$_accepted_types)),
        ), 406);
    }

    /**
     * set - Sets the data array with the argument. This
     * should be used for single row results (ie. based off an id).
     *
     * @param mixed $data
     * @access public
     * @return object $this
     */
    public function data($data = NULL)
    {
        if ($data === NULL)
        {
            return $this->_data;
        }
        if ($data instanceof ORM)
        {
            $this->_data = $data->as_array();
        }
        elseif ($data instanceof Database_Result)
        {
            $this->_data = $data->as_array()->current();
        }
        elseif ($data)
        {
            $this->_data = $data;
        }

        return $this;
    }

    /**
     * add - Append a record onto the data array. Use for
     * multirow results.
     *
     * @param mixed $data
     * @access public
     * @return object $this
     */
    public function add($data)
    {
        if ($data instanceof ORM)
        {
            foreach ($data as $record)
            {
                $this->_data[] = $record->as_array();
            }
        }
        elseif ($data instanceof Database_Result)
        {
            $data->as_array();

            while ($data->valid())
            {
                // Push the record.
                $this->_data[] = $data->current();

                // Iterate to next result.
                $data->next();
            }
        }
        elseif (is_array($data) AND $data)
        {
            if (Arr::is_assoc($data))
            {
                // Push associative array onto the data array.
                $this->_data[] = $data;
            }
            else
            {
                // Append the indexed array.
                $this->_data += $data;
            }
        }

        return $this;
    }

    /**
     * code
     *
     * @param mixed $code
     * @access public
     * @return void
     */
    public function code($code = NULL)
    {
        if ($code === NULL)
        {
            return $this->_code;
        }

        $this->_code = (int) $code;

        return $this;
    }

    /**
     * meta
     *
     * @param mixed $code
     * @access public
     * @return void
     */
    public function meta($key = NULL, $value = NULL)
    {
        if ($key === NULL)
        {
            return $this->_meta;
        }

        if ($value === NULL)
        {
            return Arr::get($this->_meta, $key);
        }

        $this->_meta[$key] = $value;

        return $this;
    }

    /**
     * format
     *
     * @param mixed $fmt
     * @access public
     * @return void
     */
    public function format($fmt = NULL)
    {
        if ($fmt === NULL)
        {
            return $this->_format;
        }

        $this->_format = $fmt;

        return $this;
    }

    /**
     * type
     *
     * @param mixed $type
     * @access public
     * @return void
     */
    public function type($type = NULL)
    {
        if ($type === NULL)
        {
            return $this->_type;
        }

        $this->_type = $type;

        return $this;
    }

    /**
     * errors
     *
     * @param mixed $msg
     * @param string $name
     * @access public
     * @return void
     */
    public function errors($msg = NULL, $name = NULL)
    {
        if ($msg === NULL)
        {
            return $this->_errors;
        }

        if (is_array($msg))
        {
            $this->_errors = $msg;

            return $this;
        }

        if ($name !== NULL)
        {
            $this->_errors[$name] = $msg;
        }
        else
        {
            $this->_errors[] = $msg;
        }

        return $this;
    }

    /**
     * render - Render the payload into the format
     * that's currently set for the object. Will also
     * set the proper headers for the initial Request
     * Response object.
     *
     * @access public
     * @return void
     */
    public function render(Request $request, Response $response)
    {
        return (string) Payload_Format::factory($this)
            ->render($this, $request, $response);
    }

    /**
     * _init
     *
     * @access protected
     * @return void
     */
    protected function _init()
    {
        $this->_code = 200;
        $this->_format = 'json';
        $this->_data = array();
        $this->_errors = array();
        $this->_meta = array();
        $this->_debug = array();
    }

    /**
     * set_debug
     *
     * @param array $data
     * @access public
     * @return void
     */
    public function debug($name = NULL, $lines = NULL, array $data = NULL)
    {
        if ($name === NULL)
        {
            return $this->_debug;
        }

        if ($lines === NULL)
        {
            return Arr::get($this->_debug, $name);
        }

        if (is_array($name) AND Arr::is_assoc($name))
        {
            $this->_debug = Arr::merge($this->_debug, $name);
        }
        else
        {
            if (is_array($lines))
            {
                array_walk($lines, function(&$value, $index, $data) {
                    if (is_string($value))
                    {
                        $value = __($value, $data);
                    }
                }, $data);

                $this->_debug[$name] = $lines;
            }
            else
            {
                $this->_debug[$name][] = __($lines, $data);
            }
        }

        return $this;
    }

    /**
     * get
     *
     * @return void
     */
    public function get($param = NULL)
    {
        $data = array();

        $fields = array('data', 'code', 'type', 'errors', 'meta');

        if (Kohana::$environment === Kohana::DEVELOPMENT)
        {
            $fields[] = 'debug';
        }

        if ($param !== NULL)
        {
            if (is_array($param) === TRUE)
            {
                $filter = array_intersect($fields, $param);

                if ($filter)
                {
                    foreach ($filter as $name)
                    {
                        $data[$param] = (isset($this->{'_'.$name})) ? $this->{'_'.$name} : NULL;
                    }

                    return $data;
                }
                else
                {
                    return NULL;
                }
            }
            else
            {
                return (in_array($param, $fields) AND isset($this->{'_'.$param})) ? $this->{'_'.$param} : NULL;
            }
        }

        foreach ($fields as $var)
        {
            $data[$var] = $this->{'_'.$var};
        }

        return $data;
    }

    /**
     * flush
     *
     * @access public
     * @return void
     */
    public function flush()
    {
        $this->_init();

        return $this;
    }

    /**
     * serialize
     *
     * @access public
     * @return void
     */
    public function serialize()
    {
        return serialize($this->get());
    }

    /**
     * unserialize
     *
     * @param mixed $data
     * @access public
     * @return void
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        foreach ($data as $key => $value)
        {
            $this->{$key}($value);
        }
    }

    /**
     * offsetExists
     *
     * @param mixed $offset '
     * @access public
     * @return void
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_data);
    }

    /**
     * offsetGet
     *
     * @param mixed $offset
     * @access public
     * @return void
     */
    public function offsetGet($offset)
    {
        return (isset($this->_data[$offset])) ? $this->_data[$offset] : NULL;
    }

    /**
     * offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     * @access public
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === NULL)
        {
            $this->_data[] = $value;
        }
        else
        {
            $this->_data[$offset] = $value;
        }
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset
     * @access public
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->_data))
        {
            unset($this->_data[$offset]);
        }
    }

}
