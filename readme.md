## Installation Dependencies

Braindump, maybe this needs some sorting :)

0. use a laravel homestead VM: http://laravel.com/docs/homestead
1. install imagick: `sudo apt-get install php5-imagick` (prefix: /urs/local)
2. install inotify: `sudo pecl install inotify` (prefix: /urs/local)
3. install zeromq:
	- sudo apt-get install pkg-config
	- sudo apt-get install libzmq3-dev
    - sudo pecl install zmq-beta  (prefix: /urs/local)
4. add imagick.so and inotify.so to the cli php.ini
5. restart php fpm: `sudo /etc/init.d/php5-fpm restart`
    