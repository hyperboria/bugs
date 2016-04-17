var Promise = require('./promise');
var fs = require('fs');

var p = new Promise(function(accept, reject) {
    setTimeout(function() {
        accept(0);
    },1000);
});

p.then(function(i) {
    return i + 19;
}).then(function(i) {
    return i + 23;
});
        
