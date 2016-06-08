<?php namespace Cjdns\Toolshed;

/* SQLite - A Class which will block the ReactPHP event loop
            if/when issue or latency occurs on the host or database.
*/

date_default_timezone_set('UTC');

class SQLite {

    public function __construct($data=false) {

        /* Calling SQLite  blocking Database
            $this->enabled
        */
        $this->database = new \medoo([
            'enabled'       => true,
            'database_type' => 'sqlite',
            'database_file' => 'peers.sqlite',
            'charset'       => 'utf8',
        ]);

        $this->database->query("create table peerstats(
            id                 integer primary key,
            date               datetime default current_timestamp,
            addr               varchar(39),
            bytesIn            varchar(16),
            bytesOut           varchar(16),
            duplicates         varchar(2),
            isIncoming         tinyint,
            last               integer,
            lostPackets        integer,
            publicKey          varchar(54),
            ipv6               varchar(39),
            receivedOutOfRange tinyint,
            recvKbps           integer,
            sendKbps           integer,
            state              varchar(15),
            switchLabel        varchar(19),
            user               varchar(20),
            version            integer
        );");

    }

    static function devdestroy($data=false) {
        if ($data) {
            $this->database->query("drop table peerstats");
        }
    }

    public function sqlite_column_fetch($database, $table = 'peerstats') {

        $table_info = $this->database->query("PRAGMA table_info($table)")->fetchAll();

        foreach ($table_info as $sqlite => $column) {
            $sqlcol[$column['name']] = true;
        }

        return $sqlcol;
    }

    public function write($table=false, $columns=false, $data=false) {

        $date = new \Datetime('now');
        $date = $date->format(\DateTime::ISO8601);

        if ($table == 'nodes') {

            $peerstats_data  = ($data['peers']) ? : [];
            $peerstats_total = ($data['total']) ? : 0;

            /************************************************/
            /* Returns an array of valid fields for sqlite */
            /************************************************/
            $valid_fields = function($a) use ($columns) {
                foreach ($columns as $name => $true) {
                    if (isset($a[$name])) {
                        $res[$name] = $a[$name];
                    }
                }
                return $res;
            };

            /* Build the array, and only allow fields found in the SQLite::__construct */
            for ($i=0; $i < $peerstats_total; $i++) {

                $peerstats_data[$i]['date'] = $date; // ISO8601 (2015-11-29T18:51:21-0500)
                $peerstats_data[$i]['ipv6'] = Toolshed::parse_peerstats($peerstats_data[$i]['addr'])['ipv6'];

                $cherrypick_data[$i] = call_user_func($valid_fields, $peerstats_data[$i]);
            }

            $this->database->insert("peerstats", $cherrypick_data);

            /* if ($latest_peerstats_table)
            foreach (['id'] as $latest_peerstats) {
                // Create a table for status webpage ie //www.fc00.h/current-peers
                $this->database->insert("peerstats_status", $cherrypick_data, "where publickey == publickey" );
            }
            */

        } else {
            return;
        }
    }

    static function peerstats_count($database, $date = false, $pubkey=false) {
        return $database->database->query('select count(*) from peerstats')->fetchAll();
    }

    static function report($database, $param = false, $pubkey=false) {

        $from   = $param['from'];
        $until  = $param['until'];
        $method = $param['method'];

        $var = $database->database->select("peerstats",
            [
                "id", "date",
                "addr", "bytesIn",
                "bytesOut", "duplicates",
                "isIncoming", "last",
                "lostPackets", "publicKey",
                "receivedOutOfRange", "recvKbps",
                "sendKbps", "state",
                "switchLabel", "user", "version"
            ],
                [
                "AND" => [
                    "publicKey" => $pubkey,
                    "date[<>]"  => [ $from, $until ]
                ],
                // "LIMIT" => [ 0, 10 ],
            ]);

        /*$database->database->log();*/

        if ($method == 'summary') {

            $resp['total'] = count($var);

            $resp['thismonth'] = $var;

        } else {

            $resp['total'] = count($var);
            $resp['data'] = $var;

        }

        return [ $resp ];
    }

}
