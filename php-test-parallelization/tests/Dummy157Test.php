<?php

use PHPUnit\Framework\TestCase;

class Dummy157Test extends TestCase
{
    public function test0()
    {
        password_hash("test", PASSWORD_DEFAULT, ["cost" => 6]);
        $this->assertTrue(true);
    }

}