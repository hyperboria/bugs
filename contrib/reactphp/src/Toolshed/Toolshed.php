<?php namespace Cjdns\Toolshed;

use Cjdns\Bencode\Bencode;

class Toolshed {

    static function logger($mesg=null) {
        if ($mesg) {
            echo Toolshed::logo() . $mesg . PHP_EOL;
        } else {
            return;
        }
    }

    static function logo() {
        return '[ᴄᴊᴅɴꜱ] ';
    }

    static function logger_http($mesg=null, $req=null) {
        if (!$mesg) {
            return;
        } else if (!$req) {
            echo Toolshed::phlogo() . $mesg . PHP_EOL;
        }


    }

    static function phlogo() {
        return '[Pʜʟᴜɪᴅ-ʜᴛᴛp] ';
    }

    static function ramusage($cfg, $verbose=false) {

        /* $kmem(int) returns formated bytes */
        $kmem = function($b) { return number_format($b / 1024). 'KiB'; };

        /* Bytes in use for server.php */
        $bytesphp = $kmem(memory_get_usage(true));

        /* Get cjdns bytes from Admin Socket */
        $data = \Cjdns\Api\Api::Allocator_bytesAllocated();
        $Socket = new \Cjdns\Admin\Socket($cfg);
        $Socket->verbose = false;
        $Socket->authput($data);

        /* Bytes in use for cjdns */
        $bytesalloc = $kmem(\Cjdns\Api\Api::decode($Socket->message)['bytes']);

        if ($verbose) {
            Toolshed::logger("Memory ReactPHP: " . $bytesphp . ", cjdns: " . $bytesalloc);
        } else {
            return "Memory ReactPHP: " . $bytesphp . ", cjdns: " . $bytesalloc;
        }
    }

    static function valid_pubkey($userinput) {
        return preg_match('/^[a-z|0-9]{52}\.k$/' , $userinput);
    }

    /* */
    static function publictoip6($pubkey = false) {
        if (!Toolshed::valid_pubkey($pubkey)) { return false; }
        $bin = '../../publictoip6 ';
        $h   = popen($bin . $pubkey, 'r');
        $ip6 = trim(fread($h, 39));

        pclose($h);
        return $ip6;
    }

    /* When given the peerstats 'addr' field string:
        v18.0000.0000.0000.0019.5jvt9cgmxjqrtr2xrz5hz2hhmgtrbfs0c5pvpj9f28gzchn5st10.k
       returns an array of the following assoc field and values:
        version: v18
        label:   0000.0000.0000.0019
        pubkey:  5jvt9cgmxjqrtr2xrz5hz2hhmgtrbfs0c5pvpj9f28gzchn5st10.k
        ipv6:    fc9f:9864:35be:e2b6:e72c:d3:e7cc:119a
    */
    static function parse_peerstats($peerstats = false) {
        if (!$peerstats) { return []; }

        $addrArray['version'] = preg_split("/\./", $peerstats)[0];

        $addrArray['label']   = preg_split("/\./", $peerstats)[1] . '.'
                              . preg_split("/\./", $peerstats)[2] . '.'
                              . preg_split("/\./", $peerstats)[3] . '.'
                              . preg_split("/\./", $peerstats)[4];

        $addrArray['pubkey']  = preg_split("/\./", $peerstats)[5] . '.k';

        $addrArray['ipv6']    = Toolshed::publictoip6($addrArray['pubkey']);

        return $addrArray;

    }

    static function ascii_logo($glue='<br>') {
        $c[] = 'mmmmm        ,,        ,,                   mmmmm';
        $c[] = 'MM           db      `7MM                      MM';
        $c[] = 'MM                     MM                      MM';
        $c[] = 'MM  ,p6"bo `7MM   ,M""bMM  `7MMpMMMb.  ,pP"Ybd MM';
        $c[] = 'MM 6M\'  OO   MM ,AP    MM    MM    MM  8I   `" MM';
        $c[] = 'MM 8M        MM 8MI    MM    MM    MM  `YMMMa. MM';
        $c[] = 'MM YM.    ,  MM `Mb    MM    MM    MM  L.   I8 MM';
        $c[] = 'MM  YMbmd\'   MM  `Wbmd"MML..JMML  JMML.M9mmmP\' MM';
        $c[] = 'MM        QO MP                                MM';
        $c[] = 'MMmmm     `bmP                              mmmMM';
        return implode($glue, $c);
    }

    static function cleanresp(Array $array, $flatten=true, $droptxrx=true) {

        /* return a single array instead of many subarray / nested results  */
        if ($flatten) {
            $array = call_user_func_array('array_merge_recursive', $array);

            foreach ([ 'count', 'deprecation', 'peers' ] as $u) {
                if (isset($array[$u])) {
                    $array[$u] = array_unique($array[$u]);
                }
            }
        }

        /* drops multiple keys (txid, more) */
        if ($droptxrx) {
            unset($array['txid']);
            unset($array['more']);
        }

        return json_encode($array);

    }

    static function msgtrim($string) {
        return substr(json_encode(Bencode::decode($string)), 0, 50);
    }

    static function sqlite_column_fetch($database, $table = 'peerstats') {

        $table_info = $database->database->query("PRAGMA table_info($table)")->fetchAll();

        foreach ($table_info as $sqlite => $column) {
            $sqlcol[$column['name']] = true;
        }

        return $sqlcol;
    }


}

