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
use BackBee\ClassContent\Iconizer\ChainIconizer;
use BackBee\ClassContent\Iconizer\ThumbnailIconizer;
use BackBee\ClassContent\Iconizer\PropertyIconizer;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 */
class ChainIconizerTest extends BackBeeTestCase
{

    private $content;
    private $chain;

    public function setUp()
    {
        $this->content = new MockContent();
        $this->content->load();

    }
    
    public function testGetIcon()
    {
	$this->content
	     ->mockedDefineData(
		'image',
		'BackBee\ClassContent\Element\Image',
		array('')
	    );
	 $this->content
	     ->mockedDefineProperty('iconized-by', 'image->path');
	 $this->content->image->path = 'aaa/test';
	
	$arrIconizer = array();
	$n = 10;	
	for($i=0;$i<=$n;$i++){
	       $arrIconizer[] = new ThumbnailIconizer(self::$app);
	}
	$this->chain = new ChainIconizer($arrIconizer);
	$test1 = $this->chain->getIcon($this->content);	
	$this->assertTrue('/resources/img/contents/default_thumbnail.png' == $test1);
	
	$arrIconizerProp = array();	
	for($i=0;$i<=$n;$i++){
	       $arrIconizerProp[] = new PropertyIconizer(self::$app->getRouting());
	}
	$this->chain = new ChainIconizer($arrIconizerProp);
	$test2 = $this->chain->getIcon($this->content);	
	$this->assertTrue($test2 == '/images/aaa/test');

    }

}
