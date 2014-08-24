<?php

use Sidney\Latchet\BaseConnection;

class Connection extends BaseConnection {

	public function open($connection)
	{

	}

	public function close($connection)
	{

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
