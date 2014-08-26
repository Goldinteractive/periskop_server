<?php

use Illuminate\Support\Fluent;
use Sidney\Latchet\BaseConnection;

class Connection extends BaseConnection {

	public function open($connection)
	{
		// Decorate the connection object with the stuff we'll need
		$connection->_number       = null; // Which number this client has in the row
		$connection->_handler      = null; // The Topic class subscribed to
		$connection->_currenttopic = null; // The actual topic (string)
		$connection->_image        = null; // The current image
		$connection->_nextimage    = null; // The next image (after the tick)
	}

	public function close($connection)
	{
		// Also remove the connection from the subscribed
		// channel if the connection is closed
		if(isset($connection->_handler))
		{
			$connection->_handler->unsubscribe($connection, 'stream');
		}
	}

	public function error($connection, $exception)
	{
		//close the connection
		$connection->close();

		// Only throw the exception if we're not in production
		if(App::environment() !== 'production')
		{
			throw new Exception($exception);
		}

		Log::error($exception);
	}

}
