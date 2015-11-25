<?php namespace Cjdns\Api;

use Cjdns\Bencode\Bencode;
use Cjdns\Toolshed\Toolshed;

class Api {

	/* cjdns api subcalls */

	static function txid() {
		return strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=10, $cstrong)));
	}

	static function hash() {
		return strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=10, $cstrong)));
	}

	static function encode($message=[]) {
		return Bencode::encode($message);
	}

	static function decode($message=[]) {
		return Bencode::decode($message);
	}

	/* cjdns api */

	static function Ping($txid=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'q'=> 'ping', 'txid'=> $txid ]);
	}

	static function AuthPing($txid=null) {
		$txid = ($txid) ? $txid : Api::txid();
		$authreq = [ 'aq' => 'ping', 'q' => 'auth', 'txid' => $txid ];
		return Bencode::encode($authreq);
	}

	static function Cookie($txid=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'q'=> 'cookie', 'txid' => $txid ]);
	}

	static function Admin_availableFunctions($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'q'=> 'Admin_availableFunctions',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function InterfaceController_peerStats($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'q'=> 'InterfaceController_peerStats',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}


}
