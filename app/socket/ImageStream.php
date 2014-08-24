<?php

use Periskop\ImageRepository;
use Sidney\Latchet\BaseTopic;

class ImageStream extends BaseTopic {

    protected $images;

    public function __construct(ImageRepository $images)
    {
        $this->images = $images;
    }

	public function subscribe($connection, $topic)
	{
		// Send a list of all the files to the new guy
		$data = array(
			'welcome_msg' => 'Benvenuto!',
			'existing_images' => $this->images->getFromDirectory('/' . trim(public_path('uploads'), '/') . '/'),
		);

		$this->broadcastEligible($topic, $data, array($connection->WAMP->sessionId));
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