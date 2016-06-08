<?php

/********************************************/
/* Display hinting reminder this application
 * depends on external external  networks to
 * update it's dependencies  and  additional
 * cjdns build-time languages such as nodejs
/********************************************/

try {

    $libmgmt = [
        'vendor/autoload.php' => [
            'hint' => "Note: PHP Dependency Manager: "
                      ."//getcomposer.org/download/"
        ],

        '../../publictoip6'   => [
            'hint' => "Compile with: ../../cjdns/do"
        ],

        '../../cjdroute'   => [
            'hint' => "Compile with: ../../cjdns/do"
        ],

        'server.php'           => [
            'hint' => "Execute within: ". $_SERVER['HOME']
                      ."/cjdns/contrib/reactphp"
        ],

    ];

    $notready = function ($v=false) use ($libmgmt) {
        foreach ($libmgmt as $file => $r) {
            if (!realpath(getcwd() . '/' . $file)) {
                return $file;
            }
        }
    };

    if ($notready()) {
        echo $libmgmt[$notready()]['hint'] . PHP_EOL;
        throw new \Exception('Missing: ' . $notready(), 1);
    }

} catch (\Exception $e) {
    trigger_error($e->getMessage());exit;
}

/*******************************************************************/

require 'vendor/autoload.php';

use Cjdns\Admin\Socket;
use Cjdns\Api\Api;
use Cjdns\Config\Admin;
use Cjdns\Toolshed\Toolshed;
use Cjdns\Toolshed\SQLite;

/*******************************************************************/
/* Database */
/*******************************************************************/
$database = new SQLite;

/*******************************************************************/
/* Authorization to Admin-API can be set in-script */
/*******************************************************************/
$cfg = new Admin([
    'addr'      => '127.0.0.1',
    'port'      => 11234,
    'password'  => 'YRZNhY4QCgUUFDnwQXGxoyuh9aaXvrNW6Tyl78JG',
    'publicKey' => 'rzvr2764sfb3lg8d9sd1d6c8jh656jky35cy86xnq52f7xqxftq0.k',
]);

/*******************************************************************/
/* Authorization by default is set via ../../cjdroute.conf */
/*******************************************************************/
$cfg = new Admin(['cfgfile' => '../../cjdroute.conf']);

/*******************************************************************/
/* HTTP Server Address */
/*******************************************************************/
$addr = Toolshed::publictoip6($cfg->publicKey);
$port = 1337;

/*******************************************************************/
/* Phluid */
/*******************************************************************/


$app = new Phluid\App();

$app->inject(new \Phluid\Middleware\ExceptionHandler('exception'));

$app->inject(new \Phluid\Middleware\RequestTimer());

$app->inject(function($request, $response, $next) use ($app) {
    $response->once('end', function() use ($request, $response, $app) {
        // https://github.com/reactphp/react/pull/228/files
        // var_dump($request->request->remoteAddress);

        Toolshed::logger_http("\t".
            "Log\t\t"  . $request.' ('.$response . ")\n\t\t".
            "Time\t\t" . $request->duration . " ms" . "\n\t\t".
            "Path\t\t" . $request->getPath() . "\n\t\t"
        );

        /* save to db ()
            $array = [
                'host' => $request->getHost(),
                'log'  => [ 'request' => $request, 'response' => $response ],
                'time' => $request->duration
            ];

            Toolshed::logger_http_save($array);
        */

    });
    $next();
});

$app->inject(function($req, $res, $next) {
    $res->setHeader('X-Powered-By', 'ReactPHP');
    $next();
});

/*******************************************************************/
/* Main */
/*******************************************************************/
$app->createServer();

$app->loop->addPeriodicTimer($databasetimer = 1, function () use ($cfg, $database) {

    $more = true; $page=0;
    $Socket = new Socket($cfg);
    $Socket->verbose = false;

    $respdata['peers'] = array();
    $respdata['total'] = 0;

    /* Add all the peerstats to the $respdata array */
    while ($more) {

        $data = Api::InterfaceController_peerStats(null, $page);
        $Socket->authput($data);

        $p = Api::decode($Socket->message);

        if (isset($p['peers'])) {
            foreach ($p['peers'] as $peer) {
                array_push($respdata['peers'], $peer);
            }
        } else {
            continue;
        }


        if (isset(Api::decode($Socket->message)['more'])) {
            $page++;
        } else {
            $respdata['total'] = Api::decode($Socket->message)['total'];
            $more = null;
        }
    }


    $columns = $database->sqlite_column_fetch($database, 'peerstats');

    /* Write out peerstats table */
    $database->write('nodes', $columns, $respdata);


    $alloc = Toolshed::ramusage($cfg);
    $peers = 'Peers cjdns: ' . $respdata['total'];

    Toolshed::logger(
        $alloc . ' | ' .
        $peers . ', ' .
        'Peers SQLite: ' . number_format($database::peerstats_count($database)[0][0])
    );

});

/* for middleware files in ./views/public/
    $app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/public'));
   see: https://github.com/beaucollins/phluid-php#middleware
*/

$app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/views/js'));
$app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/views/css'));
$app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/views/img'));

/*******************************************************************/
/* View: Display of Peer Stats in HTML Table */
/*******************************************************************/
$app->get('/', function($req, $res) use ($cfg) {

    $obj = new stdclass();
    $obj->Toolshed = new Toolshed;
    $res->render('layout', [ 'obj' => $obj ]);

});

/*******************************************************************/
/* View: Display a Helpful Method of Reporting Historical Data */
/*******************************************************************/
$app->get('/report', function ($req, $res) use ($cfg, $database) {

    /* ... print available report dates (?) */
    // $v = 'Please use: /report/[publicKey] with parameters: ';

    $obj = new stdclass();
    $obj->Toolshed = new Toolshed;
    $obj->SQLite = new SQLite;

    $res->render('report', [ 'obj' => $obj ]);

});

/*******************************************************************/
/* View: Display Historical of a Given Public Key (Work in Prog..) */
/*******************************************************************/
$app->get('/report/:pubkey', function ($request, $response, $pubkey) use ($cfg, $database) {

    /* public key of node */
    $pubkey = $request->param('pubkey');

    $method = $request->param('method');
    $from   = $request->param('from');
    $until  = $request->param('until');

    echo Toolshed::logger('Incoming: /report ('.$pubkey.') '. $method);

    /* work in progress */
    if (!$method) {

        $method = 'summary'; /* .. undefined ..*/

        if (!isset($from))  {  /* .. default .. */
            $from =  \Carbon\Carbon::today('UTC')->toIso8601String();

        } else {
            $from = new \Carbon\Carbon($from);
            $from = $from->modify()->toIso8601String();
        }

        if (!isset($until)) { /* .. default .. */
            $until = \Carbon\Carbon::now('UTC')->toIso8601String();
        } else {
            $until = new \Carbon\Carbon($until);
            $from = $from->modify()->toIso8601String();
        }

    } elseif ($method == 'summary') {
        // $from  = 'first day of this month';
        $from =  \Carbon\Carbon::now('UTC'); // ->toIso8601String();;
        $from  =  $from->modify('first day of this month')->toIso8601String();
        $until =  \Carbon\Carbon::now('UTC')->toIso8601String();

    } elseif ($method == 'phrase') {

        // $from  = 'first day of this month';
        $from  =  $from->modify()->toIso8601String();
        $until = $until->modify()->toIso8601String();

    }

    /* work in progress.... */

    $date = [
        'from'   => $from,
        'until'  => $until,
        'method' => $method
    ];

    $var = SQLite::report($database, $date, $pubkey);
    $v = json_encode($var);


    $obj = new stdclass();
    $obj->Toolshed = new Toolshed;
    $obj->SQLite = new SQLite;

    $obj->pubkey = $pubkey;
    $obj->method = $method;
    $obj->from = $from;
    $obj->until = $until;


    $response->render('report', [ 'obj' => $obj ]);


});

/*******************************************************************/
/* AJAX: Return the Data Used by Default Index */
/*******************************************************************/
$app->get('/nodes', function ($request, $response) use ($cfg, $database) {

    /******/
    $method = $request->param('q');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    /******/

    $more = true; $page=0;
    $Socket = new Socket($cfg);

    while ($more) {

        $data = Api::InterfaceController_peerStats(null, $page);
        $Socket->authput($data);
        $respdata[$page] = Api::decode($Socket->message);
        if (isset(Api::decode($Socket->message)['more'])) {
            $Socket->verbose = false;
            $page++;
        } else {
            $more = null;
        }
    }

    if ($method) {

        echo Toolshed::logger('Incoming: /nodes');

        if ($method == 'nodes') {
            /* This method returns a parsed-for-humans view for a front-end */

            for ($i=0; $i <= $page; $i++) {

                if (!isset($respdata[$i]['peers'])) { continue; }

                $peers = $respdata[$i]['peers'];

                foreach ($peers as $idx => $value) {

                    $nodes[] = [

                        // Connectivity
                        'state' => $value['state'],

                        // cjdns Address
                        'ipv6' => Toolshed::parse_peerstats($value['addr'])['ipv6'],

                        // Total RX
                        'bytesIn' => \ByteUnits\bytes($value['bytesIn'])->format(null, '&nbsp;'),

                        // Total TX
                        'bytesOut' => \ByteUnits\bytes($value['bytesOut'])->format(null, '&nbsp;'),

                        // RX Speed
                        'recvKbps' => number_format($value['recvKbps']/1000, 3) . ' mbps',

                        // TX Speed
                        'sendKbps' => number_format($value['sendKbps']/1000, 3) . ' mbps',

                        // Last Pkt
                        'last' => Carbon\Carbon::now('UTC')->diffForHumans(
                                                Carbon\Carbon::createFromTimeStampUTC(
                                                  round($value['last'] / 1000)), TRUE),

                        // Public Key
                        'publicKey' => $value['publicKey'],

                        // User
                        'user' => $value['user']


                    ];

                }
            }

            $response->end(json_encode([ 'peers' => $nodes ]));

        } else if ($method == 'chodes') {
            /* do blah */
            $response->end(json_encode($respdata));

        } else {
            echo Toolshed::logger('Incoming: /nodes');
            $response->end(json_encode([[ 'response' => 'Unknown Method' ]]));
        }

    } else {

        echo Toolshed::logger('Incoming: /nodes');

        $data = Api::InterfaceController_peerStats();
        $Socket = new Socket($cfg);
        $Socket->authput($data);

        $response->end(json_encode(Api::decode($Socket->message)));

    }

    return;
});

/*******************************************************************/
/* Available (cjdns) Functions */
/*******************************************************************/
$app->get('/help', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /help');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $more = true; $page=0;

    $Socket = new Socket($cfg);
    while ($more) {
        $data = Api::Admin_availableFunctions(null, $page);
        $Socket->put($data);

        $result[$page] = Api::decode($Socket->message);

        if (isset(Api::decode($Socket->message)['more'])) {
            $Socket->verbose = false;
            $page++;
        } else {
            $more = null;
        }
    }

    echo PHP_EOL;
    return $response->end(Toolshed::cleanresp($result, $flatten=true, $droptxrx=true));
});

/*******************************************************************/
/* Authenticated Ping */
/*******************************************************************/
$app->get('/authping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /authping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AuthPing();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

/*******************************************************************/
/* Un-Authenticated Ping */
/*******************************************************************/
$app->get('/ping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /ping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Ping();
    $Socket = new Socket($cfg);
    $Socket->put($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

/*******************************************************************/
/* Allocated Memory in Bytes */
/*******************************************************************/
$app->get('/Allocator_bytesAllocated', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /Allocator_bytesAllocated');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Allocator_bytesAllocated();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

/*******************************************************************/
/* Node Store / Dump Table */
/*******************************************************************/
$app->get('/NodeStore_dumpTable', function ($request, $response) use ($cfg) {
    /* Parameters NodeStore_dumpTable(page) */

    echo Toolshed::logger('Incoming: /NodeStore_dumpTable');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $Socket = new Socket($cfg);

    $more = true; $page=0;

    while ($more) {
        $data = Api::NodeStore_dumpTable(null, $page);
        $Socket->authput($data);

        $result[$page] = Api::decode($Socket->message);

        if (isset(Api::decode($Socket->message)['more'])) {
            $Socket->verbose = false;
            $page++;
        } else {
            $more = null;
        }
    }

    echo PHP_EOL;
    return $response->end(Toolshed::cleanresp($result, $flatten=true, $droptxrx=true));

});


Toolshed::logger('Phluid HTTP server available via cjdns @ http://['.$addr.']:'.$port);

$app->listen($port, $addr);
