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
 * @package    Zend_Serializer
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\Serializer\Adapter;

use Zend\Serializer\Exception as SerializationException,
    Zend\AMF\Parser as AMFParser;

/**
 * @uses       Zend\AMF\Parser\AMF0\Deserializer
 * @uses       Zend\AMF\Parser\AMF0\Serializer
 * @uses       Zend\AMF\Parser\InputStream
 * @uses       Zend\AMF\Parser\OutputStream
 * @uses       Zend\Serializer\Adapter\AbstractAdapter
 * @uses       Zend\Serializer\Exception
 * @category   Zend
 * @package    Zend_Serializer
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class AMF0 extends AbstractAdapter
{
    /**
     * Serialize a PHP value to AMF0 format
     * 
     * @param  mixed $value 
     * @param  array $opts 
     * @return string
     * @throws \Zend\Serializer\Exception
     */
    public function serialize($value, array $opts = array())
    {
        try  {
            $stream     = new AMFParser\OutputStream();
            $serializer = new AMFParser\AMF0\Serializer($stream);
            $serializer->writeTypeMarker($value);
            return $stream->getStream();
        } catch (\Exception $e) {
            throw new SerializationException('Serialization failed by previous error', 0, $e);
        }
    }

    /**
     * Unserialize an AMF0 value to PHP
     * 
     * @param  mixed $value 
     * @param  array $opts 
     * @return void
     * @throws \Zend\Serializer\Exception
     */
    public function unserialize($value, array $opts = array())
    {
        try {
            $stream       = new AMFParser\InputStream($value);
            $deserializer = new AMFParser\AMF0\Deserializer($stream);
            return $deserializer->readTypeMarker();
        } catch (\Exception $e) {
            throw new SerializationException('Unserialization failed by previous error', 0, $e);
        }
    }
}
