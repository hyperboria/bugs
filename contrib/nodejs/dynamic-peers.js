var path = require('path');
var os = require('os');
var fs = require('fs');
var dns = require('dns');

var Cjdns = require(
    path.join(os.homedir(),"packages","git","cjdns","tools",
              "lib","cjdnsadmin","cjdnsadmin"));

function ready(nodes) {
    Cjdns.connectWithAdminInfo(function(cjdns) {
        function link_up(publicKey,address,password) {
            cjdns.UDPInterface_beginConnection(publicKey,
                                               address,
                                               password,
                                               function() {
                                                   console.log("linked",
                                                               publicKey);
                                               }
                                              );
        }
        function lookup(node) {
            if(node.name) {
                dns.resolve(node.name,
                            function(err, addresses) { 
                                if(err) { throw err; }
                                link_up(node.publicKey,
                                        addresses[0],
                                        node.password);
                                // no need to wait for callback?
                            });
            } else {
                link_up(node.publicKey,
                        node.address,
                        node.password);
            }
        }

        
        function collectPeers(then) {
            var peers = {};
            var count = 0;
            function again(i) {
                cjdns.InterfaceController_peerStats(
                    i,function (err, ret) {
                        if (err) { throw err; }
                        ret.peers.forEach(function (peer) {
                            if(peer.state != 'UNRESPONSIVE') {
                                if(!(peer.publicKey in peers))
                                    ++count;
                                peers[peer.publicKey] = peer;
                            }
                        });
                        if (typeof(ret.more) !== 'undefined') {
                            again(i+1);
                        } else {
                            then(peers,count);
                            setTimeout(function() {
                                collectPeers(then);
                            },10000);
                        }
                    });
            }
            again(0);
        }
        
        for(var key in nodes) {
            var node = nodes[key];
            node.publicKey = key;
        }
        collectPeers(function(peers,npeers) {
            console.log("checking",npeers,"peers")
            for(var key in nodes) {
                if(key in peers) continue;
                console.log("Peer not found",key,"poking.");
                lookup(nodes[key]);
            }
        });
    });
}

var conf_name = process.env.conf;
if(!conf_name) conf_name = "cjdns_dynamic.conf";

fs.readFile(            
    path.join(os.homedir(),".config",conf_name),
    function(err, data) {
        if(err) { throw err; }
        ready(JSON.parse(data));
    });
