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
 * @package    Zend_Controller
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace ZendTest\Controller\Action\Helper;
use Zend\JSON;
use Zend\Layout;


/**
 * Test class for Zend_Controller_Action_Helper_Json
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Controller
 * @group      Zend_Controller_Action
 * @group      Zend_Controller_Action_Helper
 */
class JsonTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        \Zend\Layout\Layout::resetMvcInstance();

        $this->response = new \Zend\Controller\Response\HTTP();
        $this->response->headersSentThrowsException = false;

        $front = \Zend\Controller\Front::getInstance();
        $front->resetInstance();
        $front->setResponse($this->response);

        $this->viewRenderer = new \Zend\Controller\Action\Helper\ViewRenderer();
        \Zend\Controller\Action\HelperBroker\HelperBroker::addHelper($this->viewRenderer);
        $this->helper = new \Zend\Controller\Action\Helper\JSON();
        $this->helper->suppressExit = true;
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
    }

    public function verifyJsonHeader()
    {
        $headers = $this->response->getHeaders();

        $found = false;
        foreach ($headers as $header) {
            if ('Content-Type' == $header['name']) {
                $found = true;
                $value = $header['value'];
                break;
            }
        }
        $this->assertTrue($found);
        $this->assertEquals('application/json', $value);
    }

    public function testJsonHelperSetsResponseHeader()
    {
        $this->helper->encodeJson('foobar');
        $this->verifyJsonHeader();
    }

    public function testJsonHelperReturnsJsonEncodedString()
    {
        $data = $this->helper->encodeJson(array('foobar'));
        $this->assertTrue(is_string($data));
        $this->assertEquals(array('foobar'), JSON\JSON::decode($data));
    }

    public function testJsonHelperDisablesLayoutsAndViewRendererByDefault()
    {
        $layout = Layout\Layout::startMvc();
        $this->assertTrue($layout->isEnabled());
        $this->assertFalse($this->viewRenderer->getNoRender());
        $this->testJsonHelperReturnsJsonEncodedString();
        $this->assertFalse($layout->isEnabled());
        $this->assertTrue($this->viewRenderer->getNoRender());
    }

    public function testJsonHelperDoesNotDisableLayoutsAndViewRendererWhenKeepLayoutFlagTrue()
    {
        $layout = Layout\Layout::startMvc();
        $this->assertTrue($layout->isEnabled());
        $this->assertFalse($this->viewRenderer->getNoRender());
        $data = $this->helper->encodeJson(array('foobar'), true);
        $this->assertTrue($layout->isEnabled());
        $this->assertFalse($this->viewRenderer->getNoRender());
    }

    public function testSendJsonSendsResponse()
    {
        $this->helper->sendJson(array('foobar'));
        $this->verifyJsonHeader();
        $response = $this->response->getBody();
        $this->assertSame(array('foobar'), JSON\JSON::decode($response));
    }

    public function testDirectProxiesToSendJsonByDefault()
    {
        $this->helper->direct(array('foobar'));
        $this->verifyJsonHeader();
        $response = $this->response->getBody();
        $this->assertSame(array('foobar'), JSON\JSON::decode($response));
    }

    public function testCanPreventDirectSendingResponse()
    {
        $data = $this->helper->direct(array('foobar'), false);
        $this->assertSame(array('foobar'), JSON\JSON::decode($data));
        $this->verifyJsonHeader();
        $response = $this->response->getBody();
        $this->assertTrue(empty($response));
    }

    public function testCanKeepLayoutsWhenUsingDirect()
    {
        $layout = Layout\Layout::startMvc();
        $data = $this->helper->direct(array('foobar'), false, true);
        $this->assertTrue($layout->isEnabled());
        $this->assertFalse($this->viewRenderer->getNoRender());
    }
}
