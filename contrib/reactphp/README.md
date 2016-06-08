## ReactPHP

### How can I use blocking functions?

The short version is: *You can't*.

React is based on non-blocking I/O. Since PHP is single-threaded, that means that you cannot make any blocking calls without blocking the entire process. Which means that if you have 1K concurrent users, if each one blocks for just one millisecond, it will block the entire process for a whole second.

What are blocking functions? Pretty much any existing PHP code that touches the filesystem or the network. This includes, but is not limited to:

* `sleep`
* `file_get_contents`
* `file_put_contents`
* `mkdir`
* `fsockopen`
* `PDO`
* `mysql_*`
* *etc.*

If you need to use blocking calls, move them to a separate process and use inter-process communication.



## Cjdns

### Server

```
$ php server.php
[cjdns] ReactPHP Admin started http://127.0.0.1:1337
[cjdns] Incoming: /ping
[cjdns] Generated TXID: 77CA034DD8E3DC037830
[cjdns] Socket->put(d1:q6:cookie4:txid20:77CA034DD8E3DC037830e)
[cjdns] Socket->createClient(127.0.0.1:11234)
[cjdns] Socket->onMessage(d00006:cookie10:14142953784:txid20:77CA034DD8E3DC037830e)
[cjdns] Received Cookie: 1414295378
[cjdns] Hash Cookie: a78ea37d720a147a69d296b0a488ec8afac295f91e94953f27a8a8744a85682b
[cjdns] Socket->put(d2:aq4:ping6:cookie10:14142953784:hash64:69921f8ccfcb84793f997746389601569fbe01623e058be3ae457f83bd794b3e1:q4:auth4:txid20:77CA034DD8E3DC037830e)
[cjdns] Socket->createClient(127.0.0.1:11234)
[cjdns] Socket->onMessage(d1:q4:pong4:txid20:77CA034DD8E3DC037830e)
```

#### Client

```
$ curl localhost:1337/ping
{"q":"pong","txid":"77CA034DD8E3DC037830"}
```


#### Authenticated Ping

An authenticated ping query with the cjdns admin-api is triggered when an HTTP request event is trigged within server.php (ReactPHP & Phluid)


```PHP

/* Authenticated Ping */
$app->get('/authping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /authping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AuthPing();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end($data);
});
```
