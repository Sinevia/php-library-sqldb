<?php

class DivTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }
    public function testInit(){
        $div = new \Sinevia\Html\Div;
        $this->assertEquals($div->toHtml(),'<div></div>');
    }

    public function testAddItem(){
        $div = new \Sinevia\Html\Div;
        $div->addChild('child1');
        $div->addChild('child2');
        $div->addChild('child3');
        $this->assertEquals($div->toHtml(),'<div>child1child2child3</div>');
    }
}