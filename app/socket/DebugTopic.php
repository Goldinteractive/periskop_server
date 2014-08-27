<?php

use Sidney\Latchet\BaseTopic;

class DebugTopic extends BaseTopic {

    public function subscribe($connection, $topic)
    {
        $this->broadcastEligible($topic, array('welcome_msg' => 'Benvenuto and Welcome to the Debug Topic. You will get the latest debug info from now on fresh from the press!'), array($connection->WAMP->sessionId));
    }

    public function publish($connection, $topic, $message, array $exclude, array $eligible)
    {

    }

    public function call($connection, $id, $topic, array $params)
    {

    }

    public function unsubscribe($connection, $topic)
    {

    }

}