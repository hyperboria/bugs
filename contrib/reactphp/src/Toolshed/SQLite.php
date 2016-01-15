<?php namespace Cjdns\Toolshed;

/* SQLite - A Class which will block the ReactPHP event loop
            if/when issue or latency occurs on the host or database.
*/

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
            // $peerstats_data  = ($data['peers']) ? : [];
            // $peerstats_total = ($data['total']) ? : 0;
            return json_encode([$table, $columns, $data ]);

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

            /* Build the array */
            for ($i=0; $i < $peerstats_total; $i++) {
                /* date mutates to ISO8601 (2015-11-29T18:51:21-0500) */
                $peerstats_data[$i]['date'] = $date;
                $cherrypick_data[$i] = call_user_func($valid_fields, $peerstats_data[$i]);
            }

            $id = $database->insert("peerstats", $cherrypick_data );

            /* if ($latest_peerstats_table) */
            foreach ($id as $latest_peerstats) {
                /* Create a table for status webpage ie //www.fc00.h/current-peers */
                // $database->insert("peerstats_status", $cherrypick_data, "where publickey == publickey" );
            }

            return $id;

        } else {
            return;
        }
    }

    static function report($database, $date = false, $pubkey=false) {

        $from   = $date['from'];
        $until  = $date['until'];

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
                "LIMIT" => [ 0, 10 ],
            ]);

        /*$database->database->log();*/
        return $var;
    }

}
