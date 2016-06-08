<?php namespace Cjdns\Admin;

use Cjdns\Bencode\Bencode;
use Cjdns\Toolshed\Toolshed;
use Cjdns\Config\Admin;
use Cjdns\Api\Api;

use React\EventLoop\Factory;
use React\Datagram\Exception;

class Socket {

    protected $addr, $cfgfile, $config, $password, $port;
    protected $server;

    public $authentication = true;
    public $verbose = false;

    function __construct(Admin $cfg) {
        $this->server = $cfg->addr . ':' . $cfg->port;
        $this->password = $cfg->password;
    }


    public function authput($method) {

        /* Step 1: Request a cookie from the server. */

        $txid = Api::decode($method)['txid'];
        $this->put(Api::Cookie($txid));
        $verona = Api::decode($this->message)['cookie'];

        if ($this->verbose) {
            echo Toolshed::logger('Received Cookie: ' . $verona);
        }

        /* Step 2: Calculate the SHA-256 of your admin password cookie and the cookie,
        place this hash and cookie in the request. */

        $sha2auth = hash('sha256', $this->password . $verona);

        if ($this->verbose) {
            echo Toolshed::logger('Hash Cookie: ' . $sha2auth);
        }

        /* Step 3: Calculate the SHA-256 of the entire request with
        the hash and cookie added, replace the hash in the request with this result. */

        $hashcookie = [ 'hash' => $sha2auth, 'cookie' => $verona ];

        $prequest = array_merge($hashcookie, Api::decode($method));
        $prequest['hash'] = hash('sha256', Api::encode($prequest));
        $prequest = Api::encode($prequest);

        /* end of authentication */

        $this->put($prequest);

        $output = function($s) {
            return Toolshed::logger('Socket->authput('.$s.')');
        };

        if ( (isset(Api::decode($this->message)['txid']))   &&
             (Api::decode($this->message)['txid'] == $txid) &&
             (isset(Api::decode($this->message)['error'])) )
        {
            /* outputs when error is read (Auth failed.) */
            echo $output(Api::decode($this->message)['error']);

        } else if ($this->verbose) {
            /* error free, matches txid (supplied or generated) */
            echo $output(Toolshed::msgtrim($this->message));

        } else {
            /* error free, verbose off */
        }

        return json_encode(Api::decode($this->message));
    }

    public function put($method) {

        if ($this->verbose) {
            echo Toolshed::logger('Socket->put('.Toolshed::msgtrim($method).'...)');
        }

        $txid = Bencode::decode($method)['txid'];
        $this->txid = Bencode::decode($method)['txid'];

        $server = $this->server;
        /* ReactPHP */
        $loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);

        $factory->createClient($server)
                ->then(function (\React\Datagram\Socket $client) use ($method, $server, $loop)
        {

            if ($this->verbose) {
                echo Toolshed::logger('Socket->send('.Toolshed::msgtrim($method).'...)');
            }

            $client->send($method);

            $tid = $loop->addPeriodicTimer(2.0, function() use ($client, $method, $loop)
            {
                echo Toolshed::logger('Socket->sendTimeout('.Toolshed::msgtrim($method).'...)');

                try {
                    if (isset($tid)) {
                        $loop->cancelTimer($tid);
                        $client->end();
                    } else {
                        throw new \Exception('Sending data to cjdns admin socket timed out.', 1);
                    }
                } catch (Exception $e) {
                    echo Toolshed::logger('Socket->send('. get_class($e) .') '.
                        'thrown within the exception handler. Message: '.
                        $e->getMessage()." on line " . $e->getLine());
                    $client->end();
                }
            });

            $client->on('error', function($error, $client) {
                echo Toolshed::logger('Socket->send('. get_class($e) .') '.
                                      'Error: ' . $error->getMessage());
            });

            $client->on('message', function($message, $serverAddress, $client) use ($method, $client, $loop, $tid)
            {

                $loop->cancelTimer($tid);
                try {
                    $this->message = $message;
                    if ($this->verbose) {
                        echo Toolshed::logger('Socket->onMessage('.Toolshed::msgtrim($method).'...)');
                    }
                } catch (Exception $e) {
                    echo Toolshed::logger('Error: Socket->onMessage('.$e->getMessage().')');
                }

                $client->end();

            });

        },function($error) {
            echo Toolshed::logger('Error: Socket->createClient('.$error->getMessage().')');
        });

        $loop->run();

    }

}


?>