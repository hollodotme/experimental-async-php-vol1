<?php declare(strict_types = 1);
/**
 * @author hollodotme
 */

namespace hollodotme\AsyncPhp;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;

require(__DIR__ . '/../vendor/autoload.php');

$redisHost = '127.0.0.1';
$redisPort = 6379;

$redis     = new \Redis();
$connected = $redis->connect( $redisHost, $redisPort );

if ( $connected )
{
	echo "Connected to redis on {$redisHost}:{$redisPort}\n";

	$redis->subscribe(
		[ 'commands' ],
		function ( \Redis $redis, string $channel, string $message )
		{
			$messageArray = json_decode( $message );
			$body         = http_build_query( $messageArray );

			$connection = new UnixDomainSocket( 'unix:///var/run/php/php7.1-fpm-commands.sock' );
			$fpmClient  = new Client( $connection );

			$request = new PostRequest( '/vagrant/src/worker.php', $body );

			$processId = $fpmClient->sendAsyncRequest( $request );

			echo "Spawned process with ID: {$processId}\n";
		}
	);
}
else
{
	echo "Could not connect to redis.\n";
}
