<?php //-->
/*
 * This file is part of the Core package of the Eden PHP Library.
 * (c) 2013-2014 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE
 * distributed with this package.
 */

namespace Eden\Jabber;

use Eden\Core\Event;

/**
 * XMPP/Jabber abstract for IM clients.
 *
 * @vendor Eden
 * @package Jabber
 * @author Aldrin Bautista adinbautista@gmail.com
 */
class Base extends Event
{
    /* Constants
    --------------------------------*/
    //connection types
    const AOL_HOST = 'xmpp.oscar.aol.com';
    const AOL_PORT = 5222;

    const GOOGLE_HOST = 'gmail.com';
    const GOOGLE_PORT = 5222;

    const JABBER_HOST = 'jabber.org';
    const JABBER_PORT = 5222;

    const MSN_HOST = 'messenger.live.com';
    const MSN_PORT = 5222;

    const YAHOO_HOST = 'chat.live.yahoo.com';
    const YAHOO_PORT = 5222;

    const FACEBOOK_HOST = 'chat.facebook.com';
    const FACEBOOK_PORT = 5222;

    //presence types
    const PRESENCE_ONLINE = 'online';
    const PRESENCE_OFFLINE = 'offline';
    const PRESENCE_AWAY = 'away';
    const PRESENCE_DND = 'dnd';
    const PRESENCE_XA = 'xa';
    const PRESENCE_CHAT = 'chat';

    const PRESENCE_TYPE_PROBE = 'probe';
    const PRESENCE_TYPE_AVAILABLE = 'available';
    const PRESENCE_TYPE_UNAVAILABLE = 'unavailable';
    const PRESENCE_TYPE_ERROR = 'error';
    const PRESENCE_TYPE_SUBSCRIBE = 'subscribe';
    const PRESENCE_TYPE_SUBSCRIBED = 'subscribed';
    const PRESENCE_TYPE_UNSUBSCRIBE = 'unsubscribe';
    const PRESENCE_TYPE_UNSUBSCRIBED = 'unsubscribed';

    const MESSAGE_TYPE_CHAT = 'chat';
    const MESSAGE_TYPE_ERROR = 'error';
    const MESSAGE_TYPE_GROUPCHAT = 'groupchat';
    const MESSAGE_TYPE_HEADLINE = 'headline';
    const MESSAGE_TYPE_NORMAL = 'normal';

    const AUTH_NOOP = 0;
    const AUTH_STARTED = 1;
    const AUTH_CHALLENGE = 2;
    const AUTH_FAILIURE = 3;
    const AUTH_PROCEED = 4;
    const AUTH_SUCCESS = 5;

    const AUTH_TYPE_STREAM = 'stream:stream';
    const AUTH_TYPE_FEATURES = 'stream:features';
    const AUTH_TYPE_CHALLENGE = 'challenge';
    const AUTH_TYPE_FAILURE = 'failure';
    const AUTH_TYPE_PROCEED = 'proceed';
    const AUTH_TYPE_SUCCESS = 'success';

    const QUERY_TYPE_BIND = 'bind_1';
    const QUERY_TYPE_SESSION = 'sess_1';
    const QUERY_TYPE_REGISTER = 'reg_1';
    const QUERY_TYPE_REGISTERED = 'reg_2';
    const QUERY_TYPE_UNREGISTER = 'unreg_1';
    const QUERY_TYPE_ROSTER = 'roster_1';
    const QUERY_TYPE_PUSH = 'push';

    /* Public Properties
    --------------------------------*/
    /* Protected Properties
    --------------------------------*/
    protected $host = null;
    protected $port = null;
    protected $user = null;
    protected $pass = null;

    protected $ssl = false;
    protected $tls = false;

    protected $negotiation = self::AUTH_NOOP;

    protected $connection = null;
    protected $jabberId = null;
    protected $streamId = null;
    protected $presence = null;
    protected $session = false;

    protected $resource = null;

    protected static $presences = array(
        self::PRESENCE_ONLINE,
        self::PRESENCE_CHAT,
        self::PRESENCE_OFFLINE,
        self::PRESENCE_DND,
        self::PRESENCE_AWAY,
        self::PRESENCE_XA);

    protected static $presenceTypes = array(
        self::PRESENCE_TYPE_PROBE,
        self::PRESENCE_TYPE_UNAVAILABLE,
        self::PRESENCE_TYPE_ERROR,
        self::PRESENCE_TYPE_SUBSCRIBE,
        self::PRESENCE_TYPE_SUBSCRIBED,
        self::PRESENCE_TYPE_UNSUBSCRIBE,
        self::PRESENCE_TYPE_UNSUBSCRIBED);

    protected static $messageTypes = array(
        self::MESSAGE_TYPE_CHAT,
        self::MESSAGE_TYPE_ERROR,
        self::MESSAGE_TYPE_GROUPCHAT,
        self::MESSAGE_TYPE_HEADLINE,
        self::MESSAGE_TYPE_NORMAL);

    protected static $authentications = array(
        self::AUTH_TYPE_STREAM,
        self::AUTH_TYPE_FEATURES,
        self::AUTH_TYPE_CHALLENGE,
        self::AUTH_TYPE_FAILURE,
        self::AUTH_TYPE_PROCEED,
        self::AUTH_TYPE_SUCCESS);

    protected static $queries = array(
        self::QUERY_TYPE_BIND,
        self::QUERY_TYPE_SESSION,
        self::QUERY_TYPE_REGISTER,
        self::QUERY_TYPE_REGISTERED,
        self::QUERY_TYPE_UNREGISTER,
        self::QUERY_TYPE_ROSTER,
        self::QUERY_TYPE_PUSH);

     /**
     * Connects to the remote server
     *
     * @param string host
     * @param int port
     * @param string username
     * @param string password
     * @param boolean usesSsl
     * @param boolean uses tls
     * @return void
     */
    public function __construct(
        $host,
        $port,
        $user,
        $pass,
        $ssl = false,
        $tls = true
    ) {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'int')
            ->test(3, 'string')
            ->test(4, 'string')
            ->test(5, 'bool')
            ->test(6, 'bool');

        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->domain = $host;

        $this->ssl = $ssl && $this->canUseSSL();
        $this->tls = $tls && $this->canUseTLS();

        if (strpos($user, '@') !== false) {
            list($this->user, $this->domain) = explode('@', $user);
        }

        // Change port if we use SSL
        if ($this->port == 5222 && $this->ssl) {
            $this->port = 5223;
        }
    }

    /* Public Methods
    --------------------------------*/
    /**
     * Connects to the remote server
     *
     * @param number timeout
     * @return Eden\Jabber\Base
     */
    public function connect($timeout = 10)
    {
        Argument::i()->test(1, 'int', 'null');

        //if already connected
        if ($this->connection) {
            //do nothing more
            return $this;
        }

        $host = $this->host;

        //if dns_get_record function exists
        if (function_exists('dns_get_record')) {
            //get the dns record
            $record = @dns_get_record("_xmpp-client._tcp.$host", DNS_SRV);

            //if the record is not empty
            if (!empty($record) && !empty($record[0]['target'])) {
                //set the target to be the host
                $host = $record[0]['target'];
            }
        }

        //fix for ssl
        if ($this->ssl) {
            $host = 'ssl://' . $host;
        }

        //try to open a connection
        try {
            $this->connection = fsockopen(
                $host,
                $this->port,
                $errorno,
                $errorstr,
                $timeout
            );
        } catch (_Exception $e) {
            //throw an exception
            Exception::i()->setMessage(Exception::CONNECTION_FAILED)
                ->addVariable($host)
                ->addVariable($this->port)
                ->trigger();
        }

        socket_set_blocking($this->connection, 0);
        socket_set_timeout($this->connection, 60);

        //send off what a typical jabber server opens with
        $this->send("<?xml version='1.0' encoding='UTF-8' ?>\n");
        $this->send(
            "<stream:stream to='".$this->host."' xmlns='jabber:client' ".
            "xml:lang='en' xmlns:xml='http://www.w3.org/XML/1998/namespace' ".
            "xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>\n"
        );
        $this->trigger('connected');

        //start waiting
        $this->response($this->wait());

        return $this;
    }

    /**
     * Disconnects from the server
     *
     * @return Eden\Jabber\Base
     */
    public function disconnect()
    {
        //if its not connected
        if (!$this->connection) {
            return $this;
        }

        // disconnect gracefully
        if (isset($this->presence)) {
            $this->setPresence(
                self::PRESENCE_OFFLINE,
                self::PRESENCE_OFFLINE,
                null,
                'unavailable'
            );
        }

        //close the stream
        $this->send('</stream:stream>');

        //close the connection
        fclose($this->connection);

        $this->connection = null;

        $this->trigger('disconnected');

        return $this;
    }

    /**
     * Returns Meta Data
     *
     * @return array
     */
    public function getMeta()
    {
        return array(
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'ssl' => $this->ssl,
            'tls' => $this->tls,
            'negotiation' => $this->negotiation,
            'connection' => $this->connection,
            'jabberId' => $this->jabberId,
            'streamId' => $this->streamId,
            'presence' => $this->presence,
            'session' => $this->session);
    }

    /**
     * Check to see who is online
     *
     * @param to string
     * @return Eden\Jabber\Base
     */
    public function probe($to)
    {
        Argument::i()->test(1, 'string');

        return $this->setPresence($to, null, self::PRESENCE_TYPE_PROBE);
    }

    /**
     * Sends xml data to host
     *
     * @param string xml
     * @return Eden\Jabber\Base
     */
    public function send($xml)
    {
        Argument::i()->test(1, 'string');

        //if not connected
        if (!$this->connection) {
            //throw exception
            Exception::i(Exception::NOT_CONNECTED)->trigger();
        }

        //clean the XML
        $xml = trim($xml);

        $this->trigger('sent', $xml);

        //send the XML off
        fwrite($this->connection, $xml);

        return $this;
    }

    /**
     * Set the presence to away
     *
     * @param string|array to
     * @param string message
     * @return Eden\Jabber\Base
     */
    public function setAway($to = null, $message = null)
    {
        Argument::i()
            ->test(1, 'string', 'array', 'null')
            ->test(2, 'string', 'null');

        return $this->setPresence($to, $message, null, self::PRESENCE_AWAY);
    }

    /**
     * Set the presence to DND
     *
     * @param string|array to
     * @param string message
     * @return Eden\Jabber\Base
     */
    public function setDND($to = null, $message = null)
    {
        Argument::i()
            ->test(1, 'string', 'array', 'null')
            ->test(2, 'string', 'null');

        return $this->setPresence($to, $message, null, self::PRESENCE_DND);
    }

    /**
     * Set the presence to offline
     *
     * @param string|array to
     * @param string message
     * @return Eden\Jabber\Base
     */
    public function setOffline($to = null, $message = null)
    {
        Argument::i()
            ->test(1, 'string', 'array', 'null')
            ->test(2, 'string', 'null');

        return $this->setPresence($to, $message, null, self::PRESENCE_OFFLINE);
    }

    /**
     * Set the presence to online
     *
     * @param string|array to
     * @param string message
     * @return Eden\Jabber\Base
     */
    public function setOnline($to = null, $message = null)
    {
        Argument::i()
            ->test(1, 'string', 'array', 'null')
            ->test(2, 'string', 'null');

        return $this->setPresence($to, $message, null, self::PRESENCE_TYPE_AVAILABLE);
    }

    /**
     * Set the presence of a user
     *
     * @param string|array to
     * @param string message
     * @param string type
     * @param string presence title
     * @return Eden\Jabber\Base
     */
    public function setPresence(
        $to = null,
        $message = null,
        $type = null,
        $show = null
    ) {
        Argument::i()
            ->test(1, 'array', 'string', 'null')
            ->test(2, 'string', 'null')
            ->test(3, 'string', 'null')
            ->test(4, 'string', 'null');

        //if no JID
        if (!isset($this->jabberId)) {
            //throw exception
            Exception::i(Exception::NO_JID)->trigger();
        }

        //fix show
        $show = strtolower($show);
        $show = in_array($show, self::$presences) ? '<show>'. $show .'</show>'
            : null;

        if($show == self::PRESENCE_ONLINE)
            $show = null;

        //fix type
        $type = in_array($type, self::$presenceTypes) ? ' type="'.$type.'"'
            : null;

        //fix from
        $from = 'from="'.$this->jabberId.'"';

        //fix message
        switch(true) {
            case $message:
                $message = '<status>' . htmlspecialchars($message) .'</status>';
                break;
            default:
                $message = null;
                break;
        }

        $template = '<presence '.$from.'%s'.$type.' />';
        if ($show || $message) {
            $template = '<presence '.$from.'%s'.$type.'>'
                .$show.$message.'</presence>';
        }

        if (is_null($to)) {
            $this->send(sprintf($template, ''));
            return $this;
        }

        //if to is a string
        if (is_string($to)) {
            //make it into an array
            $to = array($to);
        }

        //walk to
        foreach ($to as $user) {
            //fix to
            switch(true) {
                case $user:
                    $to = ' to="'.$user.'"';
                    break;
                default:
                    $to = '';
                    break;
            }
            //send prensense to user
            $this->send(sprintf($template, $to));
        }

        return $this;
    }

    /**
     * Defines a resource name.
     * This is usuall your app name.
     *
     * @param string
     * @return Eden\Jabber\Base
     */
    public function setResource($name)
    {
        Argument::i()->test(1, 'string');
        $this->resource = $name;
        return $this;
    }

    /**
     * Requests for roster
     *
     * @param string message
     * @return Eden\Jabber\Base
     */
    public function getRoster()
    {
        $this->send(
            '<iq from="'.$this->jabberId.'" type="get" id="roster_1">'.
            '<query xmlns="jabber:iq:roster"/></iq> '
        );
        return $this;
    }

    /**
     * Set the presence to XA ?
     *
     * @param string|array to
     * @param string message
     * @return Eden\Jabber\Base
     */
    public function setXA($to = null, $message = null)
    {
        Argument::i()
            ->test(1, 'string', 'array', 'null')
            ->test(2, 'string', 'null');

        return $this->setPresence($to, $message, null, self::PRESENCE_XA);
    }

    /**
     * Generic start up
     *
     * @return Eden\Jabber\Base
     */
    public function start()
    {
        $this->connect();
        while ($this->connection) {
            set_time_limit(60);
            $response = $this->response($this->wait());
            if ($response === false) {
                break;
            }
        }

        return $this->disconnect();
    }

    /**
     * Set the presence of a user
     *
     * @param string|array to
     * @param string message
     * @return Eden\Jabber\Base
     */
    public function subscribeTo($to = null, $message = null)
    {
        Argument::i()
            ->test(1, 'string', 'array', 'null')
            ->test(2, 'string', 'null');

        $this->send(
            sprintf(
                '<iq type="set" id="set1"><query xmlns='.
                '"jabber:iq:roster"><item jid="%s" /></query></iq>',
                $to
            )
        );

        return $this->setPresence($to, $message, self::PRESENCE_TYPE_SUBSCRIBE);
    }

    /**
     * Sends a message to a user
     *
     * @param string to whom to send to
     * @param string text
     * @param string subject
     * @param string message type
     * @return Eden\Jabber\Base
     */
    public function to($to, $text, $subject = null, $thread = null)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string')
            ->test(3, 'string', 'null')
            ->test(4, 'string', 'null');

        //if no JID
        if (!isset($this->jabberId)) {
            //throw exception
            Exception::i(Exception::NO_JID)->trigger();
        }


        $from = $this->jabberId;

        if (!$thread) {
            $template = '<message from="%s" to="%s" type="%s" id="%s">'.
            '<subject>%s</subject><body>%s</body></message>';

            return $this->send(sprintf(
                $template,
                htmlspecialchars($from),
                htmlspecialchars($to),
                self::MESSAGE_TYPE_NORMAL,
                uniqid('msg'),
                htmlspecialchars($subject),
                htmlspecialchars($text)
            ));
        }


        $template = '<message from="%s" to="%s" type="%s" id="%s">'.
        '<subject>%s</subject><body>%s</body><thread>%s</thread>'.
        '<active xmlns="http://jabber.org/protocol/chatstates" />'.
        '</message>';

        return $this->send(sprintf(
            $template,
            htmlspecialchars($from),
            htmlspecialchars($to),
            self::MESSAGE_TYPE_CHAT,
            uniqid('msg'),
            htmlspecialchars($subject),
            htmlspecialchars($text),
            $thread
        ));
    }

    /**
     * Listens for imcoming data
     *
     * @param int
     * @return string XML
     */
    public function wait($timeout = 10)
    {
        Argument::i()
            ->test(1, 'int');
        //if not connected
        if (!$this->connection) {
            //throw exception
            Exception::i(Exception::NOT_CONNECTED)->trigger();
        }

        $start = time();
        $data = '';

        do {
            //get the incoming data
            $read = trim(fread($this->connection, 4096));
            //append it to the buffer
            $data .= $read;
            //keep going till timeout or connection was terminated or data is complete (denoted by > )
        } while (
            time() <= $start + $timeout &&
            !feof($this->connection) &&
            (
                $data == '' ||
                $read != '' ||
                ( substr(rtrim($data), -1) != '>' )
            )
        );

        //if there is data
        if ($data != '') {
            $this->trigger('received', $data);
            //parse the xml and return
            return $this->parseXml($data);
        } else {
            //return nothing
            return null;
        }
    }

    /* Protected Methods
    --------------------------------*/
    /**
     * authenticates
     *
     * @param  string $command
     * @param  array $xml
     * @return Eden\Jaber\Base
     */
    protected function authenticate($command, $xml)
    {
        Argument::i()
            ->test(1, 'string', 'null')
            ->test(2, 'array');
        //response switch
        switch ($command) {
            case self::AUTH_TYPE_STREAM:
                // Connection initialized (or after authentication). Not much to do here...
                if (isset($xml['stream:stream'][0]['#']['stream:features'])) {
                    // we already got all info we need
                    $features = $xml['stream:stream'][0]['#'];
                } else {
                    $features = $this->wait();
                }

                $second_time = isset($this->streamId);
                $this->streamId = $xml['stream:stream'][0]['@']['id'];

                if ($second_time) {
                    // If we are here for the second time after TLS, we need to continue logging in
                    //if there are no features
                    if (!sizeof($features)) {
                        //throw exception
                        Exception::i(Exception::NO_FEATURES)->trigger();
                    }

                    return $this->response($features);
                }

                //we are on the first step
                $this->negotiation = self::AUTH_STARTED;

                // go on with authentication?
                if (isset($features['stream:features'][0]['#']['mechanisms']) || $this->negotiation == self::AUTH_PROCEED) {
                    return $this->response($features);
                }

                break;

            case self::AUTH_TYPE_FEATURES:
                // Resource binding after successful authentication
                if ($this->negotiation == self::AUTH_SUCCESS) {
                    // session required?
                    $this->session = isset($xml['stream:features'][0]['#']['session']);

                    $this->send(
                        "<iq type='set' id='bind_1'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'>".
                        "<resource>" . htmlspecialchars($this->resource) . '</resource></bind></iq>'
                    );

                    return $this->response($this->wait());
                }

                // Let's use TLS if SSL is not enabled and we can actually use it
                if (!$this->ssl && $this->tls && $this->canUseSSL() &&
                    isset($xml['stream:features'][0]['#']['starttls'])) {
                    $this->send("<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>\n");
                    return $this->response($this->wait());
                }

                // Does the server support SASL authentication?

                // I hope so, because we do (and no other method).
                if (isset($xml['stream:features'][0]['#']['mechanisms'][0]['@']['xmlns']) &&
                    $xml['stream:features'][0]['#']['mechanisms'][0]['@']['xmlns'] == 'urn:ietf:params:xml:ns:xmpp-sasl') {
                    // Now decide on method
                    $methods = array();

                    foreach ($xml['stream:features'][0]['#']['mechanisms'][0]['#']['mechanism'] as $value) {
                        $methods[] = $value['#'];
                    }

                    // we prefer DIGEST-MD5
                    // we don't want to use plain auth (neither does the server usually) if no encryption is in place

                    // http://www.xmpp.org/extensions/attic/jep-0078-1.7.html
                    // The plaintext mechanism SHOULD NOT be used unless the underlying stream is encrypted(using SSL or TLS)
                    // and the client has verified that the server certificate is signed by a trusted certificate authority.

                    if (in_array('DIGEST-MD5', $methods)) {
                        $this->send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>");
                    } else if (in_array('PLAIN', $methods) && ($this->ssl || $this->negotiation == self::AUTH_PROCEED)) {
                        $this->send(
                            "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>"
                            . base64_encode(chr(0) . $this->user . '@' . $this->domain . chr(0) . $this->pass) .
                            '</auth>'
                        );
                    } else if (in_array('ANONYMOUS', $methods)) {
                        $this->send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='ANONYMOUS'/>");
                    } else {
                        // not good...
                        //disconnect
                        $this->disconnect();
                        //throw an exception
                        Exception::i(Exception::NO_AUTH_METHOD)->trigger();
                    }

                    return $this->response($this->wait());
                }

                // ok, this is it. bye.
                //disconnect
                $this->disconnect();
                //throw an exception
                Exception::i(Exception::NO_SASL)->trigger();
                break;

            case self::AUTH_TYPE_CHALLENGE:
                // continue with authentication...a challenge literally -_-
                $this->negotiation = self::AUTH_CHALLENGE;
                $decoded = base64_decode($xml['challenge'][0]['#']);
                $decoded = $this->parseData($decoded);

                if (!isset($decoded['digest-uri'])) {
                    $decoded['digest-uri'] = 'xmpp/'. $this->host;
                }

                // better generate a cnonce, maybe it's needed
                $decoded['cnonce'] = base64_encode(md5(uniqid(mt_rand(), true)));

                // second challenge?
                if (isset($decoded['rspauth'])) {
                    $this->send("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>");
                } else {
                    // Make sure we only use 'auth' for qop (relevant for $this->encryptPass())
                    // If the <response> is choking up on the changed parameter we may need to adjust _encryptPass() directly
                    if (isset($decoded['qop']) && $decoded['qop'] != 'auth' && strpos($decoded['qop'], 'auth') !== false) {
                        $decoded['qop'] = 'auth';
                    }

                    $response = array(
                        'username' => $this->user,
                        'response' => $this->encryptPass(array_merge($decoded, array('nc' => '00000001'))),
                        'charset' => 'utf-8',
                        'nc' => '00000001',
                        'qop' => 'auth');// only auth being supported

                    foreach (array('nonce', 'digest-uri', 'realm', 'cnonce') as $key) {
                        if (isset($decoded[$key])) {
                            $response[$key] = $decoded[$key];
                        }
                    }

                    $this->send(
                        "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>" .
                        base64_encode($this->implodeData($response)) . '</response>'
                    );
                }

                return $this->response($this->wait());

            case self::AUTH_TYPE_FAILURE:
                // if failed
                $this->negotiation = self::AUTH_FAILIURE;
                $this->trigger('failiure');
                //disconnect
                $this->disconnect();
                //throw an exception
                return Exception::i(Exception::SERVER_FAILED)->trigger();

            case self::AUTH_TYPE_PROCEED:
                // continue switching to TLS
                $meta = stream_get_meta_data($this->connection);
                socket_set_blocking($this->connection, 1);

                if (!stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    //'Error: TLS mode change failed.'
                    Exception::i(Exception::SERVER_FAILED)->trigger();
                }

                socket_set_blocking($this->connection, $meta['blocked']);
                $this->negotiation = self::AUTH_PROCEED;

                // new stream
                $this->send("<?xml version='1.0' encoding='UTF-8' ?" . ">\n");
                $this->send(
                    "<stream:stream to='".$this->host."' xmlns='jabber:client' ".
                    "xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>\n"
                );

                return $this->response($this->wait());

            case self::AUTH_TYPE_SUCCESS:
                // Yay, authentication successful.
                $this->send(
                    "<stream:stream to='".$this->host."' xmlns='jabber:client' ".
                    "xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>\n"
                );

                $this->negotiation = self::AUTH_SUCCESS;

                // we have to wait for another response
                return $this->response($this->wait());
        }

        return $this;
    }

    /**
     * checks if we can user ssl
     *
     * @return boolean
     */
    protected function canUseSSL()
    {
        return @extension_loaded('openssl');
    }

    /**
     * checks if we can user TLS
     *
     * @return boolean
     */
    protected function canUseTLS()
    {
        return @extension_loaded('openssl')
        && function_exists('stream_socket_enable_crypto')
        && function_exists('stream_get_meta_data')
        && function_exists('socket_set_blocking')
        && function_exists('stream_get_wrappers');
    }

    /**
     * encrypt the password data
     *
     * @param  array $data
     * @return string
     */
    protected function encryptPass($data)
    {
        Argument::i()
            ->test(1, 'array');
        // let's me think about <challenge> again...
        foreach (array('realm', 'cnonce', 'digest-uri') as $key) {
            if (!isset($data[$key])) {
                $data[$key] = '';
            }
        }

        $pack = md5($this->user . ':' . $data['realm'] . ':' . $this->pass);

        if (isset($data['authzid'])) {
            $a1 = pack('H32', $pack) .
                sprintf(
                    ':%s:%s:%s',
                    $data['nonce'],
                    $data['cnonce'],
                    $data['authzid']
                );
        } else {
            $a1 = pack('H32', $pack).
                sprintf(':%s:%s', $data['nonce'], $data['cnonce']);
        }

        // should be: qop = auth
        $a2 = 'AUTHENTICATE:'. $data['digest-uri'];

        return md5(
            sprintf(
                '%s:%s:%s:%s:%s:%s',
                md5($a1),
                $data['nonce'],
                $data['nc'],
                $data['cnonce'],
                $data['qop'],
                md5($a2)
            )
        );
    }

    /**
     * [getDepth description]
     * @param  array $vals
     * @param  integer $i
     * @return array
     */
    protected function getDepth($vals, &$i)
    {
        Argument::i()
            ->test(1, 'array')
            ->test(2, 'int');
        $children = array();

        if (isset($vals[$i]['value'])) {
            array_push($children, $vals[$i]['value']);
        }

        while (++$i < sizeof($vals)) {
            switch ($vals[$i]['type']) {
                case 'open':
                    $tagname = (isset($vals[$i]['tag'])) ? $vals[$i]['tag'] : '';
                    $size = (isset($children[$tagname])) ? sizeof($children[$tagname]) : 0;

                    if (isset($vals[$i]['attributes'])) {
                        $children[$tagname][$size]['@'] = $vals[$i]['attributes'];
                    }

                    $children[$tagname][$size]['#'] = $this->getDepth($vals, $i);

                    break;

                case 'cdata':
                    array_push($children, $vals[$i]['value']);
                    break;

                case 'complete':
                    $tagname = $vals[$i]['tag'];
                    $size = (isset($children[$tagname])) ? sizeof($children[$tagname]) : 0;
                    $children[$tagname][$size]['#'] = (isset($vals[$i]['value']))
                        ? $vals[$i]['value'] : array();

                    if (isset($vals[$i]['attributes'])) {
                        $children[$tagname][$size]['@'] = $vals[$i]['attributes'];
                    }

                    break;

                case 'close':
                    return $children;
                    break;
            }
        }

        return $children;
    }

    /**
     * implodes an array
     *
     * @param  array $data
     * @return string
     */
    protected function implodeData($data)
    {
        Argument::i()
            ->test(1, 'array');

        $return = array();
        foreach ($data as $key => $value) {
            $return[] = $key . '="' . $value . '"';
        }
        return implode(',', $return);
    }

    /**
     * executes a command
     *
     * @param  string $command
     * @param  array $xml
     * @return Eden\Jabber\Base
     */
    protected function query($command, $xml)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'array');

        // multiple possibilities here
        switch ($command) {
            case self::QUERY_TYPE_BIND:
                $this->jabberId = $xml['iq'][0]['#']['bind'][0]['#']['jid'][0]['#'];
                $this->trigger('loggedin');
                // and (maybe) yet another request to be able to send messages *finally*
                if ($this->session) {
                    $this->send(
                        "<iq to='".$this->host."' type='set' id='sess_1'>
                        <session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>"
                    );

                    return $this->response($this->wait());
                }

                return $this;

            case self::QUERY_TYPE_SESSION:
                $this->trigger('session');
                return $this;

            case self::QUERY_TYPE_REGISTER:
                $this->send(
                    "<iq type='set' id='reg_2'><query xmlns='jabber:iq:register'><username>" .
                    htmlspecialchars($this->user)."</username><password>".htmlspecialchars($this->pass) .
                    "</password></query></iq>"
                );

                return $this->response($this->wait());

            case self::QUERY_TYPE_REGISTERED:
                // registration end
                if (isset($xml['iq'][0]['#']['error'])) {
                    //'Warning: Registration failed.'
                    return $this;
                }

                $this->trigger('registered');

                return $this;

            case self::QUERY_TYPE_UNREGISTER:
                $this->trigger('unregistered');
                return $this;

            case self::QUERY_TYPE_ROSTER:
                if (!isset($xml['iq'][0]['#']['query'][0]['#']['item'])) {
                    //'Warning: No Roster Returned.'
                    $this->trigger('roster', array());
                    return $this;
                }

                $roster = array();
                foreach ($xml['iq'][0]['#']['query'][0]['#']['item'] as $item) {
                    $jid = $item['@']['jid'];
                    $subscription = $item['@']['subscription'];
                    $roster[$jid] = $subscription;
                }

                $this->trigger('roster', $roster);
                break;

            case 'push':
                if (!isset($xml['iq'][0]['#']['query'][0]['#']['item'][0]['@']['ask'])) {
                    //'Request for push denied.'
                    return $this;
                }

                $attributes = $xml['iq'][0]['#']['query'][0]['#']['item'][0]['@'];

                if ($attributes['ask'] == 'subscribe'
                 && strpos($this->user.'@'.$this->domain, $attributes['jid']) === false) {
                    $this->setPresence(
                        self::PRESENCE_CHAT,
                        self::ONLINE,
                        $attributes['jid'],
                        'subscribed'
                    );

                    $this->setPresence(
                        self::PRESENCE_CHAT,
                        self::ONLINE,
                        $attributes['jid']
                    );
                }

                $this->trigger('subscribe', $attributes['ask'], $attributes['jid']);
                break;
            //'Notice: Received unexpected IQ.'
            default:
                break;
        }

        return $this;
    }

    /**
     * formats the data
     *
     * @param  string $data
     * @return array
     */
    protected function parseData($data)
    {
        Argument::i()
            ->test(1, 'string');

        $data = explode(',', $data);
        $pairs = array();
        $key = false;

        foreach ($data as $pair) {
            $dd = strpos($pair, '=');

            if ($dd) {
                $key = trim(substr($pair, 0, $dd));
                $pairs[$key] = trim(trim(substr($pair, $dd + 1)), '"');
            } else if (strpos(strrev(trim($pair)), '"') === 0 && $key) {
                // We are actually having something left from "a, b" values, add it to the last one we handled.
                $pairs[$key] .= ',' . trim(trim($pair), '"');
                continue;
            }
        }

        return $pairs;
    }

    /**
     * parses the xml
     *
     * @param  xml String  $data
     * @param  integer $skip_white
     * @param  string  $encoding
     * @return array
     */
    protected function parseXml($data, $skip_white = 1, $encoding = 'UTF-8')
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'int', 'null')
            ->test(3, 'string', 'null');

        $data = trim($data);

        //if the data does not start with an XML header
        if (substr($data, 0, 5) != '<?xml') {
            // modify the data
            $data = '<root>'. $data . '</root>';
        }

        $vals = $index = $array = array();

        //parse xml to an array
        $parser = xml_parser_create($encoding);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $skip_white);
        xml_parse_into_struct($parser, $data, $vals, $index);
        xml_parser_free($parser);

        $i = 0;
        //get the tag name
        $tagname = $vals[$i]['tag'];

        $array[$tagname][0]['@'] = (isset($vals[$i]['attributes'])) ? $vals[$i]['attributes'] : array();
        $array[$tagname][0]['#'] = $this->getDepth($vals, $i);

        //if the data does not start with an XML header
        if (substr($data, 0, 5) != '<?xml') {
            //get the root
            $array = $array['root'][0]['#'];
        }

        return $array;
    }

    /**
     * processes the response
     *
     * @param  array
     * @return Eden\Jabber\Base
     */
    protected function response($xml)
    {
        Argument::i()
            ->test(1, 'array');

        //if the xml is not an array
        //or if it is empty
        if (!is_array($xml) || !sizeof($xml)) {
            //do nothing
            return $this;
        }

        // did we get multiple elements? do one after another
        // array('message' => ..., 'presence' => ...)
        if (sizeof($xml) > 1) {
            foreach ($xml as $key => $value) {
                $this->response(array($key => $value));
            }

            return $this;
            // or even multiple elements of the same type?
            // array('message' => array(0 => ..., 1 => ...))
        } else if (sizeof(reset($xml)) > 1) {
            foreach (reset($xml) as $value) {
                $this->response(array(key($xml) => array(0 => $value)));
            }
            return $this;
        }

        $command = key($xml);

        if (in_array($command, self::$authentications)) {
            return $this->authenticate($command, $xml);
        }

        if ($command == 'iq') {
            // we are not interested in IQs we did not expect
            if (!isset($xml['iq'][0]['@']['id'])) {
                return $this;
            }

            $command = $xml['iq'][0]['@']['id'];

            return $this->query($command, $xml);
        }

        if ($command == 'message') {
            // we are only interested in content...
            if (!isset($xml['message'][0]['#']['body'])) {
                return $this;
            }

            $from = $xml['message'][0]['@']['from'];
            $to = $xml['message'][0]['@']['to'];
            $body = $xml['message'][0]['#']['body'][0]['#'];
            //sometimes the message received is that they are just fishing for who
            //will respond we should notify them of our presence
            //we will let whomever deal with this
            $fishing = $to != $this->jabberId;

            $subject = null;
            if (isset($xml['message'][0]['#']['subject'])) {
                $subject = $xml['message'][0]['#']['subject'][0]['#'];
            }

            $thread = null;
            if (isset($xml['message'][0]['#']['thread'])) {
                $thread = $xml['message'][0]['#']['thread'][0]['#'];
            }

            $this->trigger('message', $from, $body, $subject, $thread, $fishing);

            return $this;
        }
    }
}
