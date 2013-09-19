<?php
/**
 * Ajax Controller Test Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Tests
 * @author   Erich Duda <dudaerich@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:unit_tests Wiki
 */
namespace VuFindTest\Controller;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
 * Ajax Controller Test Class
 *
 * @category VuFind2
 * @package  Tests
 * @author   Erich Duda <dudaerich@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:unit_tests Wiki
 */
class AjaxControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;
    
    /**
     * Setup: load configuration
     */
    public function setUp()
    {
        $this->setApplicationConfig(
            include VUFIND_PHPUNIT_MODULE_PATH . '/../config/module.config.php'
        );
        parent::setUp();
    }
    
    /**
     * Test getFacetsAjax method
     *
     * @return void
     */
    public function testGetFacetsAjaxFirstLevel()
    {
    	$this->dispatch(
    			"/AJAX/JSON",
    			"GET",
    			array(
    					"method" => "getFacets",
    					"facetName" => "institution",
    					"facetPrefix" => "0/MZK",
    					"facetLevel" => "0",
    			)
    	);
    	$this->assertResponseStatusCode(200);
    	$response = json_decode($this->getResponse()->getContent(), true);
    	file_put_contents("/home/erich/log", print_r($response, true));
    	$this->assertTrue(strtolower($response['status']) == 'ok');
    
    	foreach($response['data'] as $facet) {
    		$this->assertTrue($this->startsWith("0/MZK", $facet['value']));
    	}
    }
    
    /**
     * Test getFacetsAjax method
     * 
     * @return void
     */
    public function testGetFacetsAjaxSecondLevel()
    {
        $this->dispatch(
            "/AJAX/JSON",
            "GET",
            array(
                "method" => "getFacets",
                "facetName" => "institution",
                "facetPrefix" => "0/MZK",
                "facetLevel" => "1",
            )
        );
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        file_put_contents("/home/erich/log2", print_r($response, true));
        $this->assertTrue(strtolower($response['status']) == 'ok');
        
        foreach($response['data'] as $facet) {
            $this->assertTrue($this->startsWith("1/MZK", $facet['value']));
        }
    }
    
    private function startsWith($needle, $haystack) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}