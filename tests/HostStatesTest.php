<?php

/**
 * Created by PhpStorm.
 * User: livruen
 * Date: 09.02.16
 * Time: 16:24
 */
class HostStatesTest extends PHPUnit_Framework_TestCase
{
    public $test;

    public function setup(){
        $this->test = new HostStates();
    }
    public function testGetState(){
        $state = $this->test->getState(0);
        $this->assertsTrue($state == 0);
    }
}
