// Sample Javascript instrumentation file for Web Test.
// (C) 2018 CubicleSoft.  All Rights Reserved.

var WebTestHook__global = {
	'onerror' : window.onerror,
	'scriptpath' : ''
};

// Find the script tag.
for (var x = 0; x < document.scripts.length; x++) {
	if (document.scripts[x].src && document.scripts[x].src !== '' && document.scripts[x].src.indexOf('/js_instrument.js') > -1) {
		WebTestHook__global.scriptpath = document.scripts[x].src.substring(0, document.scripts[x].src.indexOf('/js_instrument.js'));
	}
}

// Don't break chaining.
if (WebTestHook__global.scriptpath !== '') {
	WebTestHook__global.onerror = window.onerror;
	window.onerror = function(message, file, line, col, errorObj) {
		var data = '\'' + message + '\' in \'' + file + '\' on line ' + line;
		if (col !== null)  data += ', col ' + col;
		if (errorObj !== null && typeof(errorObj.stack) === 'string' && errorObj.stack !== '')  data += ', Stack trace:\n' + errorObj.stack;

		var request = new XMLHttpRequest();
		request.open('POST', WebTestHook__global.scriptpath + '/js_instrument.php', true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		request.send('message=' + encodeURIComponent(data));

		return (typeof(WebTestHook__global.onerror) === 'function' ? WebTestHook__global.onerror(arguments) : false);
	}
}
