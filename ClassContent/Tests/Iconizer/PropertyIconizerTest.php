<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\ClassContent\Tests\Iconizer;

use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\Tests\BackBeeTestCase;
use BackBee\ClassContent\Iconizer\PropertyIconizer;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 */

class PropertyIconizerTest extends BackBeeTestCase {
    
    private $content;
    private $property;

    public function setUp()
    {
        $this->content = new MockContent();
        $this->content->load();
	$this->property = new PropertyIconizer(self::$app->getRouting());
    }
    
    public function testGetIcon(){

	$prop = $this->property->getIcon($this->content);
	$this->assertNull($prop);
	
	$this->content
	     ->setProperty('iconized-by', 'image->path');
	
	$this->content	 
	    ->mockedDefineData(
		'imageTest',
		'BackBee\ClassContent\Element\Image',
		array()
	    );
	
	$this->content   ->mockedDefineData(
		'image',
		'BackBee\ClassContent\Element\Image',
		array()
	    );
	$this->content->image->path = 'aaa/test';

	$prop1 = $this->property->getIcon($this->content);

	$this->assertTrue($prop1 == '/images/aaa/test');	

	$this->content->image->path = 'test1/test2';
	$prop2 = $this->property->getIcon($this->content);
	$this->assertTrue($prop2 == '/images/test1/test2');
	
	$this->content
	     ->setProperty('iconized-by', '@imageParam');
	$this->content->mockedDefineParam('imageParam', 'aaa/eee');

	$prop3 = $this->property->getIcon($this->content);
	$this->assertTrue($prop3 == '/aaa/eee');
	
	$this->content
	     ->setProperty('iconized-by', '@imageTest');
	$this->content->mockedDefineParam('imageTest', 'test');
	$prop4 = $this->property->getIcon($this->content);
	$this->assertTrue($prop4 == '/test');

    }
    
}