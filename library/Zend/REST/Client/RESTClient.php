<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Rest
 * @subpackage Client
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\REST\Client;

use Zend\URI;

/**
 * @uses       Zend\REST\Client\Exception
 * @uses       Zend\REST\Client\Result
 * @uses       Zend\Service\AbstractService
 * @uses       Zend\URI\URI
 * @uses       Zend\URI\URL
 * @category   Zend
 * @package    Zend_Rest
 * @subpackage Client
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class RESTClient extends \Zend\Service\AbstractService
{
    /**
     * Data for the query
     * @var array
     */
    protected $_data = array();

     /**
     * Zend_Uri of this web service
     * @var \Zend\Uri\Http
     */
    protected $_uri = null;

    /**
     * Constructor
     *
     * @param string|\Zend\Uri\Http $uri URI for the web service
     * @return void
     */
    public function __construct($uri = null)
    {
        if (!empty($uri)) {
            $this->setUri($uri);
        }
    }

    /**
     * Set the URI to use in the request
     *
     * @param string|Zend\URI\URI $uri URI for the web service
     * @return Zend\REST\Client\RESTClient
     */
    public function setUri($uri)
    {
        if ($uri instanceof URI\URI) {
            $this->_uri = $uri;
        } else {
            $this->_uri = new URI\URL($uri);
        }

        return $this;
    }

    /**
     * Retrieve the current request URI object
     *
     * @return Zend\URI\URI
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * Call a remote REST web service URI and return the Zend_Http_Response object
     *
     * @param  string $path            The path to append to the URI
     * @throws Zend\REST\Client\Exception
     * @return void
     */
    final private function _prepareRest($path)
    {
        // Get the URI object and configure it
        if (!$this->_uri instanceof URI\URI) {
            throw new Exception('URI object must be set before performing call');
        }

        $uri = $this->_uri->generate();

        if ($path[0] != '/' && $uri[strlen($uri)-1] != '/') {
            $path = '/' . $path;
        }

        $this->_uri->setPath($path);

        /**
         * Get the HTTP client and configure it for the endpoint URI.  Do this 
         * each time as the Zend\HTTP\Client instance may be shared with other 
         * Zend\Service\AbstractService subclasses.
         */
        $this->getHttpClient()->resetParameters()->setUri($this->_uri);
    }

    /**
     * Performs an HTTP GET request to the $path.
     *
     * @param string $path
     * @param array  $query Array of GET parameters
     * @throws Zend\HTTP\Client\Exception
     * @return Zend\HTTP\Response\Response
     */
    final public function restGet($path, array $query = null)
    {
        $this->_prepareRest($path);
        $client = $this->getHttpClient();
        $client->setParameterGet($query);
        return $client->request('GET');
    }

    /**
     * Perform a POST or PUT
     *
     * Performs a POST or PUT request. Any data provided is set in the HTTP
     * client. String data is pushed in as raw POST data; array or object data
     * is pushed in as POST parameters.
     *
     * @param mixed $method
     * @param mixed $data
     * @return \Zend\HTTP\Response\Response
     */
    protected function _performPost($method, $data = null)
    {
        $client = $this->getHttpClient();
        if (is_string($data)) {
            $client->setRawData($data);
        } elseif (is_array($data) || is_object($data)) {
            $client->setParameterPost((array) $data);
        }
        return $client->request($method);
    }

    /**
     * Performs an HTTP POST request to $path.
     *
     * @param string $path
     * @param mixed $data Raw data to send
     * @throws \Zend\HTTP\Client\Exception
     * @return \Zend\HTTP\Response\Response
     */
    final public function restPost($path, $data = null)
    {
        $this->_prepareRest($path);
        return $this->_performPost('POST', $data);
    }

    /**
     * Performs an HTTP PUT request to $path.
     *
     * @param string $path
     * @param mixed $data Raw data to send in request
     * @throws \Zend\HTTP\Client\Exception
     * @return \Zend\HTTP\Response\Response
     */
    final public function restPut($path, $data = null)
    {
        $this->_prepareRest($path);
        return $this->_performPost('PUT', $data);
    }

    /**
     * Performs an HTTP DELETE request to $path.
     *
     * @param string $path
     * @throws \Zend\HTTP\Client\Exception
     * @return \Zend\HTTP\Response\Response
     */
    final public function restDelete($path)
    {
        $this->_prepareRest($path);
        return $this->getHttpClient()->request('DELETE');
    }

    /**
     * Method call overload
     *
     * Allows calling REST actions as object methods; however, you must
     * follow-up by chaining the request with a request to an HTTP request
     * method (post, get, delete, put):
     * <code>
     * $response = $rest->sayHello('Foo', 'Manchu')->get();
     * </code>
     *
     * Or use them together, but in sequential calls:
     * <code>
     * $rest->sayHello('Foo', 'Manchu');
     * $response = $rest->get();
     * </code>
     *
     * @param string $method Method name
     * @param array $args Method args
     * @return \Zend\REST\Client\RESTClient_Result|\Zend\REST\Client\RESTClient \Zend\REST\Client\RESTClient if using
     * a remote method, Zend_Rest_Client_Result if using an HTTP request method
     */
    public function __call($method, $args)
    {
        $methods = array('post', 'get', 'delete', 'put');

        if (in_array(strtolower($method), $methods)) {
            if (!isset($args[0])) {
                $args[0] = $this->_uri->getPath();
            }
            $this->_data['rest'] = 1;
            $data = array_slice($args, 1) + $this->_data;
            $response = $this->{'rest' . $method}($args[0], $data);
            $this->_data = array();//Initializes for next Rest method.
            return new Result($response->getBody());
        } else {
            // More than one arg means it's definitely a Zend_Rest_Server
            if (sizeof($args) == 1) {
                // Uses first called function name as method name
                if (!isset($this->_data['method'])) {
                    $this->_data['method'] = $method;
                    $this->_data['arg1']  = $args[0];
                }
                $this->_data[$method]  = $args[0];
            } else {
                $this->_data['method'] = $method;
                if (sizeof($args) > 0) {
                    foreach ($args as $key => $arg) {
                        $key = 'arg' . $key;
                        $this->_data[$key] = $arg;
                    }
                }
            }
            return $this;
        }
    }
}
