var Promise = require('./promise');
var fs = require('fs');

function dummyPromise() {
    return new Promise(function(accept, reject) {
        setTimeout(function() {
            accept(0);
        },1);
    });
}
var p = dummyPromise();

p.then(function herp(i) {
    return i + 19;
}).then(function derp(i) {
    return i + 23;
}).then(function done(i) {
    if(i != 42) {
        throw new Error("Fail");
    }
    dummyPromise()
        .then(function(i) {
            throw new Error("failure");
        },function(e) {
            console.log("should never get here!");
        })
        .then(function(i) {
            console.log("or here!");
        },function(e) {
            //console.log("got error",e);
            throw new Error("something else went wrong");
        })
        .catch(function(e) {
            //console.log("we caught e",e);
            return 42;
        })
        .then(function(res) {
            if(42 != res) throw new Error("fail...");
        });
});

        
