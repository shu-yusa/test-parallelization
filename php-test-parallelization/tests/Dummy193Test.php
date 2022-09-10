<?php

use PHPUnit\Framework\TestCase;

class Dummy193Test extends TestCase
{
    public function test0()
    {
        password_hash("test", PASSWORD_DEFAULT, ["cost" => 6]);
        $this->assertTrue(true);
    }

    public function test1()
    {
        password_hash("test", PASSWORD_DEFAULT, ["cost" => 6]);
        $this->assertTrue(true);
    }

}