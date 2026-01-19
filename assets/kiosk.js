// assets/kiosk.js
var Kiosk = (function() {
    var uid = null;
    var interval = 15000;
    var timer = null;
    var heartbeatUrl = 'heartbeat.php';
    var logoutUrl = 'logout.php';

    function postJSON(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            keepalive: true
        }).then(function(resp){ return resp.json(); });
    }

    function sendHeartbeat() {
        if (!uid) return;
        fetch(heartbeatUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({uid: uid}),
            keepalive: true
        }).catch(function(){ /* ignore errors */ });
    }

    function start() {
        if (!uid) return;
        sendHeartbeat();
        timer = setInterval(sendHeartbeat, interval);
    }

    function stop() {
        if (timer) clearInterval(timer);
    }

    function doBeaconLogout() {
        if (!uid) return;
        var data = JSON.stringify({uid: uid});
        // sendBeacon supports arrayBuffer or blob/text
        if (navigator.sendBeacon) {
            var blob = new Blob([data], {type: 'application/json'});
            navigator.sendBeacon(logoutUrl, blob);
        } else {
            // fallback sync XHR (not ideal)
            var xhr = new XMLHttpRequest();
            xhr.open('POST', logoutUrl, false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            try { xhr.send(data); } catch(e){}
        }
    }

    window.addEventListener('beforeunload', function(e){
        // prova a fare logout via beacon
        doBeaconLogout();
    });

    return {
        init: function(opts){
            uid = opts.uid || null;
            interval = opts.heartbeatInterval || interval;
            heartbeatUrl = opts.heartbeatUrl || heartbeatUrl;
            logoutUrl = opts.logoutUrl || logoutUrl;
            start();
        },
        stop: stop
    };
})();
