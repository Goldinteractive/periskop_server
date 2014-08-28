<?php

use Periskop\ImageStack;
use Periskop\ImageRepository;
use Sidney\Latchet\BaseTopic;
use Illuminate\Events\Dispatcher as EventDispatcher;

class ImageStream extends BaseTopic {

    /**
     * Periskop\ImageRepository instance
     *
     * @var object
     */
    protected $images;

    /**
     * Array holding
     * all subscribers which actually registered with
     * their assigned number
     *
     * @var arrray
     */
    protected $clients;

    /**
     * Illuminate\Events\Dispatcher instance
     *
     * @var object
     */
    protected $events;

    /**
     * Periskop\ImageStack instance holding
     * all the images
     *
     * @var object
     */
    protected $stack;

    /**
     * true, if we're waiting for clients
     * to give feedback if they loaded the current
     * image and are ready for the effect/animation
     *
     * @var  boolean
     */
    protected $waiting = array();

    /**
     * Tmp variable to hold ticks (used for
     * random image inject)
     *
     * @var integer
     */
    protected $random_tick = 0;

    /**
     * Force guests holds an array of unique hashs
     * which can be used for 'external' clients to connect
     * to the application.
     *
     * @var array
     */
    protected $allowed_guests = array(
        'dr48r' => 1000,
        '89dwz' => 1001,
        '1xlq6' => 1002,
    );

    public function __construct(ImageRepository $images, EventDispatcher $events, ImageStack $stack)
    {
        $this->images = $images;
        $this->clients = array();
        $this->events = $events;
        $this->stack = $stack;

        $that = $this;

        $this->events->listen('ticktack', function($timer) use ($that){
            $that->tick($timer);
        });
    }

    public function subscribe($connection, $topic)
    {
        $connection->_currenttopic = $topic;
        $this->debug(array('log_msg' =>  'New subscriber with session id ' . $connection->WAMP->sessionId . ', this client has not yet joined the Periskop app!'));
        $this->broadcastEligible($topic, array('welcome_msg' => 'Benvenuto!'), array($connection->WAMP->sessionId));
    }

    public function publish($connection, $topic, $message, array $exclude, array $eligible)
    {

    }

    public function call($connection, $id, $topic, array $params)
    {
        $this->debug(array('log_msg' =>  'New message from Number ' . $connection->_number, 'data' => $params));

        if(array_get($params, 'action', null) !== null)
        {
            switch (array_get($params, 'action')) {
                case 'join':
                    $this->clientRegistration($connection, $params);
                    break;
                case 'loaded':
                    $this->clientLoadedImage($connection);
                    break;
                default:
                    //something went wrong
                    $connection->close();
                    break;
            }
        }
        else
        {
            // GTFO!
            $connection->close();
        }
    }

    public function unsubscribe($connection, $topic)
    {
        $this->debug(array('log_msg' =>  'Number ' . $connection->_number .' just disconnected.'));
        unset($this->clients[$connection->_number]);

        // if we're still waiting on an image from this guy
        if(array_key_exists($connection->WAMP->sessionId, $this->waiting))
        {
            unset($this->waiting[$connection->WAMP->sessionId]);
            $this->debug(array('log_msg' =>  'Funny thing: we were still waiting on an image response of Number ' . $connection->_number .' while he disconnected.'));
        }
    }

    /**
     * This function is triggered in an interval ever X seconds.
     * The interval is defined in the ListenCommand directly.
     * We'll check if there are any new images available and if
     * yes, push them to the image stack
     *
     * @param  object  $timer  React\EventLoop\Timer\Timer
     * @return void
     */
    protected function tick($timer)
    {
        if(count($this->clients) === 0)
        {
            $this->debug(array('log_msg' =>  'TickTack, entered the loop but noone is connected'));
            return;
        }

        if(count($this->waiting) !== 0)
        {
            $numbers_open = array();

            foreach ($this->waiting as $waiting)
            {
                $numbers_open[] = $waiting->_number;
            }

            $this->debug(array('log_msg' =>  'We are still waiting on the following numbers: ' . implode(', ', $numbers_open)));
            return;
        }


        $this->debug(array('log_msg' =>  'TickTack, entered the timer loop'));

        $newest = $this->images->getMostRecent();
        $this->images->moveFiles();

        if($newest !== null)
        {
            $this->random_tick = 0;
            $this->debug(array('log_msg' =>  'New Image added to the stack!', 'data' => $newest));
            $this->stack->push($newest);
        }
        elseif($this->random_tick >= 10)
        {
            // No new images added via FTP, just get a random one from the final image folder
            $rnd = $this->images->getRandom();
            $this->debug(array('log_msg' =>  'New RANDOM Image added to the stack!', 'data' => $rnd));
            $this->stack->push($rnd);
        }

        $this->random_tick++;
        $this->broadcastImageStack();
    }

    /**
     *
     */
    protected function broadcastImageStack()
    {
        $clients = new CachingIterator(new ArrayIterator($this->clients), CachingIterator::TOSTRING_USE_CURRENT);
        $loops = 0;

        foreach ($clients as $key => $connection)
        {
            if($clients->hasNext())
            {
                $next_connection = $clients->getInnerIterator()->current();
                $next_connection->_nextimage = $connection->_image;
            }

            // If this is the first client in line, check if there's
            // a new image on the stack and if yes, add it
            if($loops === 0)
            {
                // We have a new image on the stack, let's
                // display it
                if(!$this->stack->isEmpty())
                {
                    $connection->_nextimage = $this->stack->pop();
                }
                else
                {
                    $connection->_nextimage = null;
                }

                $loops++;
            }

            $connection->_image = $connection->_nextimage;

            // Only notify client if he really has an
            // image assigned to him, also add this client
            // to the array of waiting clients.
            if($connection->_image !== null)
            {
                $this->waiting[$connection->WAMP->sessionId] = $connection;
                $this->broadcastEligible($connection->_currenttopic, array('action' => 'add', 'image' => $connection->_image), array($connection->WAMP->sessionId));

                $this->debug(array('log_msg' =>  'client ' . $key . ' should load image: ' . $connection->_image['big']['name']));
            }
            else
            {
                $this->debug(array('log_msg' =>  'client ' . $key . ' should not load any image '));
            }
        }
    }

    /**
     * A client notified us that the last image we sent him now is
     * preloaded and ready to show. If all the $this->waiting
     * clients responded, we can notifiy them that they should
     * display the image.
     *
     * @param  object  $client  Ratchet\Wamp\WampConnection
     * @return void
     */
    protected function clientLoadedImage($connection)
    {
        if(array_key_exists($connection->WAMP->sessionId, $this->waiting))
        {
            unset($this->waiting[$connection->WAMP->sessionId]);
        }

        if(count($this->waiting) === 0)
        {
            $this->broadcast($connection->_currenttopic, array('action' => 'effect'));
        }
    }

    /**
     * An already subscribed client wants to really join the party
     * with a number
     *
     * @param  object  $client  Ratchet\Wamp\WampConnection
     * @param  array   $params
     * @return void
     */
    protected function clientRegistration($connection, array $params)
    {
        $number = $this->resolveNumber(array_get($params, 'number', null));

        // We need a valid 'number', otherwise -> GTFO!
        if($number === null or !is_int($number))
        {
            $connection->close();
            return;
        }

        // Check if this number is already occupied
        $occupied = array_filter($this->clients, function($client) use ($number){
            return $client->_number == $number;
        });

        if(count($occupied) !== 0)
        {
            $connection->close();
            return;
        }

        // Everything was ok, assigne number and add to clients list
        $connection->_number = $number;
        $connection->_handler = $this;
        $connection->joined = true;

        $this->debug(array('log_msg' =>  'Connection with number ' . $number . ' added with ip ' . $connection->remoteAddress));
        $this->clients[$number] = $connection;
        ksort($this->clients); // Make sure they're in the correct order
    }

    /**
     * If a client connects, he has a number (for the correct order).
     * For guest access, this can also be a hash. We're trying to
     * resolve a hash if one exists and kick the client which already
     * uses the hash out of the stack
     *
     * @param  mixed    $number
     * @return integer
     */
    protected function resolveNumber($number)
    {
        if(array_key_exists($number, $this->allowed_guests))
        {
            // this is a valid hash, so we have to check if no one is
            // connected with this number already
            $number = $this->allowed_guests[$number];

            foreach ($this->clients as $client)
            {
                // ok we found one with this number/hash
                if($client->_number === $number)
                {
                    $this->debug(array('log_msg' =>  'Connection with number ' . $number . ' got force-kiked out, because someone else reused the hash'));
                    $this->broadcastEligible($client->_currenttopic, array('action' => 'kill'), array($client->WAMP->sessionId));
                }
            }
        }

        return $number;
    }

    /**
     * Publish debug messages to seperate Topic
     *
     * @param   array   $data  [description]
     * @return  void
     */
    protected function debug(array $data)
    {
        Latchet::publish('debug', $data);
    }

}