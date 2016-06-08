<?php namespace Cjdns\Config;

use Cjdns\Admin\Socket;

class Admin {

    public function __construct($cfg=false) {

        /* Sets the few $required fields */
        $setupadmin = function($req=false) {
            try {
                if (!$req) {
                    throw new \Exception("Error no Admin-API fields were given "
                                         . "Exception @ Admin.php\n", 1);
                } else {
                    $this->addr      = $req['addr'];
                    $this->password  = $req['password'];
                    $this->port      = $req['port'];
                    $this->publicKey = $req['publicKey'];
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

        };


        /* Executes cjdroute --cleanconf
            returns false if any $req fields
            are missing.
        */
        $cleanconf = function($cfgfile) use ($setupadmin) {

            $bin = '../../cjdroute --cleanconf ';
            $h   = popen($bin . ' < ' . $cfgfile, 'r');
            $cfg = json_decode(trim(fread($h, 56000)));
            pclose($h);

            if (!is_object($cfg) || !isset($cfg->admin)) {
                return false;
            }

            $req = [
                'addr'     => (isset(explode(':', $cfg->admin->bind)[0]))
                                ? explode(':', $cfg->admin->bind)[0]
                                : false,

                'port'     => (isset(explode(':', $cfg->admin->bind)[1]))
                                ? explode(':', $cfg->admin->bind)[1]
                                : false,

                'password' => (isset($cfg->admin->password))
                                ? $cfg->admin->password
                                : false,

                'publicKey' => (isset($cfg->publicKey))
                                ? $cfg->publicKey
                                : false
            ];

            /* Admin-API values written to config (even if false) */
            $setupadmin($req);

            /* If any of the Admin-API values are false, return false */
            return (in_array(false, $req)) ? false : $req;

        };


        if (isset($cfg['cfgfile'])) {

            $this->cfgfile = $cfg['cfgfile'];

            try {
                /* Verify $cfgfile exists */
                if (!@file_get_contents($this->cfgfile)) {
                    throw new \Exception("Error Locating cjdroute.conf file "
                                        . "($this->cfgfile) Exception @ ", 1);

                } else {

                    /* Write the Admin-API fields to the class */
                    $setupadmin($cleanconf($this->cfgfile));

                    /* Verify $cfgfile contains Admin-API IP, Port, Pass */
                    if (!$cleanconf($this->cfgfile)) {
                        throw new \Exception(
                            "Error Cleaning $this->cfgfile JSON "
                            . "(../../cjdroute --cleanconf) or missing "
                            . "any of the required Admin-API fields: IP, Port, Pass", 0);
                    }
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            /* end of try */

        /* end of cfgfile */
        } elseif (!isset($cfg['cfgfile'])) {

            try {

                foreach (['addr', 'password', 'port', 'publicKey'] as $req) {

                    if (!isset($cfg[$req])) {
                        throw new \Exception(
                            "Missing Admin-API field '$req' Exception @ Admin.php", 1
                        );
                    }
                } /* end of foreach */

                /* Continue unless an Exception was thrown */
                $setupadmin($cfg);

            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        } /* end in-script key/values (no cjdroute.conf file path supplied to Admin.php) */
    } /* end of construct */
}
