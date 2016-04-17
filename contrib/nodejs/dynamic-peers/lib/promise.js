// because depending on acorn is dumb

var sentinel = "unset";

var Promise = function(callback) {
    var result = sentinel;
    var err = sentinel;
    var stages = [];
    function doit(res,mode) {
        if(result !== sentinel) {
            throw new Error("Don't call this twice");
        }
        // don't you love tail recursion?
        if(mode === ACCEPT) {
            result = res;
        } else {
            err = res;
        }
        while(stages) {
            var stage = stages[0];
            try {
                if(mode === ACCEPT) {
                    res = stage.ok(res);
                    stages.pop();
                } else {
                    res = stage.err(err,res);
                    mode = ACCEPT;
                    stages.pop();
                }
            } catch(e) {
                mode = REJECT;
                err = e;
                stages.pop();
            }                
        }
    }
    function accept(res) {
        return doit(res,ACCEPT);
    }
    function reject(e) {
        return doit(res,REJECT);
    }
    return {
        then: function(ok,notok) {
            // if already set, don't bother with stages
            if(err !== sentinel && notok) {
                notok(err,result);
                return this;
            }
            if(result !== sentinel) {
                ok(result);
                return this;
            }
            var stage = {
                ok: ok,
                err: notok
            };
            stages.push(stage);
            return this;
        },
        catch: function(notok) {
            return this.then(undefined,notok);
        }
    }
}

module.exports = Promise;

Promise.wrap = function(fn) {
    var that = this;
    var args = Array.prototype.slice.call(arguments, 1);
    return new Promise(function(accept,reject) {
        args.push(function(err, res) {
            if(err) reject(err);
            else accept(res);
        });
        try { fn.apply(that, args); }
        catch(e) {
            reject(e);
        }
    });
}

Promise.all = function(promises) {
    return new Promise(function(accept,reject) {
        var results = new Array(promises.length);
        var i = 0;
        var count = 0;
        for(var promise of promises) {
            ++count;
            promise.then(function(res) {
                results[i] = res;
                if(count == 0) {
                    accept(results);
                } else {
                    --count;
                }
            },function(err) {
                reject(err);
            });
        }
    });
}
