<?php
/*
 * This file is part of the Image package of the Eden PHP Library.
 * (c) 2013-2014 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE
 * distributed with this package.
 */

class Eden_Jaber_Tests_Jabber_JabberTest extends \PHPUnit_Framework_TestCase {
    public function setup()
    {
        $this->jabberAccount = 'adin234@wtfismyip.com';
        $this->jabber = eden(
            'jabber',
            'wtfismyip.com',
            5222,
            $this->jabberAccount,
            'password1'
        );
        $this->jabber->connect();
    }

    public function testProbe() {
        $this->jabber->setOnline();
        print_r($this->jabber->getMeta());
    }
}