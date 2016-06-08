<?php namespace Cjdns\Api;

use Cjdns\Bencode\Bencode;
use Cjdns\Toolshed\Toolshed;

class Api {

	/* cjdns api subcalls */

	static function txid($txid=null) {
		return (isset($txid))
				? $txid
				: strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=10, $cstrong)));
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

	static function Ping($txid=null, $page=0) {
		$txid = Api::txid($txid);
		return Bencode::encode([ 'q'=> 'ping',
								 'txid'=> $txid
							   ]);
	}

	static function AuthPing($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'ping',
								 'q' => 'auth',
								 'txid' => $txid
							   ]);
	}

	static function Cookie($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'q'=> 'cookie',
			                     'txid' => $txid
							   ]);
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
		return Bencode::encode([ 'aq'=> 'InterfaceController_peerStats',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Allocator_bytesAllocated($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Allocator_bytesAllocated',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function NodeStore_dumpTable($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'NodeStore_dumpTable',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

}
