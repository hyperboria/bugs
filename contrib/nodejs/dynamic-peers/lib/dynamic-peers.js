var path = require('path');
var os = require('os');
var fs = require('fs');
var dns = require('dns');
var Promise = require('./promise');

var Cjdns = require('cjdns');

var readFile = Promise.wrap(fs.readFile);
var resolve = Promise.wrap(dns.resolve);

var conf_name = process.env.conf;
if(!conf_name) conf_name = "cjdns_dynamic.conf";

readFile
(path.join(os.homedir(),".config",conf_name))
.then(JSON.parse)
.then(function (nodes) {
    Cjdns.connectWithAdminInfo(function(cjdns) {
        const peerStats =
              Promise.denodify(cjdns.InterfaceController_peerStats);
        
        function link_up(node) {
            cjdns.UDPInterface_beginConnection(node.publicKey,
                                               node.address,
                                               node.password,
                                               function() {
                                                   console.log("linked",
                                                               publicKey);
                                               }
                                              );
        }
        function lookup(node) {
            if(node.name) {
                resolve(node.name)
                .then(function (addresses) {
                    node.address = addresses[0];
                    link_up(node);
                    // no need to wait for callback?
                },function(err) {
                    print(err);
                    link_up(node)
                });
            } else {
                link_up(node);
            }
        }

        function collectPeers(then) {
            var peers = {};
            var count = 0;
            function again(i) {
                peerStats(i).then(function(ret) {
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
});


