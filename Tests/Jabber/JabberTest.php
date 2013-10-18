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

    public function testOnline() {
        $this->jabber->setOnline();
        $meta = $this->jabber->getMeta();

        echo 'online';
        sleep(3);
        print_r($meta);
    }

    public function testProbe() {
        $this->jabber->probe('aldrin234@wtfismyip.com');
        $meta = $this->jabber->getMeta();

        echo 'probe';
        sleep(3);
        print_r($meta);
    }

    public function testAway() {
        $this->jabber->setAway();
        $meta = $this->jabber->getMeta();

        echo 'away';
        sleep(3);
        print_r($meta);
    }

    public function testTo() {
        $this->jabber->to('aldrin234@wtfismyip.com', 'pangit ka forever', 'available');
        $meta = $this->jabber->getMeta();

        echo 'send message';
        sleep(3);
        print_r($meta);
    }

    public function testSetPresence() {
        $this->jabber->setPresence('aldrin234@wtfismyip.com', 'online presence');
        $meta = $this->jabber->getMeta();

        echo 'setPresence';
        sleep(3);
        print_r($meta);

        $this->jabber->setAway();
        $meta = $this->jabber->getMeta();

        echo 'away';
        sleep(3);
        print_r($meta);
    }

    public function testOffline() {
        $this->jabber->setOffline();
        $meta = $this->jabber->getMeta();

        echo 'offline';
        sleep(3);
        print_r($meta);
    }
}