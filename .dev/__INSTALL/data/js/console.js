/** Get from http://mabp.kiev.ua/2009/01/05/console2/ */
var cast = function(i) {
	// call lazy function definition
	return  (cast = !document.fileSize  // IE any version
		? function(i){return Array.prototype.slice.call(i);}
		: function(i){var l=i.length,a=[];while(l--){a[l]=i[l];}return a;}
	)(i);
},

show = function(m) {
	return (show=window.opera?window.opera.postError:window.alert)(m);
},

dump = function (x, max, sep, l) {
	l = l || 0, max = max || 3, sep = sep || "\t";
	if (l > max) return "[WARNING: Too much recursion]\n";
	var i, r = '', t = typeof x, tab = '';

	if (x === null) {
		r += "(null)\n";
	} else if (t == "object") {
		for (i=0,l++;i<l;i++) {tab += sep;}
		if (x && (x.length || x.length==0)) {t = 'array';}
		r += "(" + t + ") :\n";
		for (i in x)
			try { r += tab + "[" + i + "] : " + dump(x[i], max, sep, l+1);} catch(e) { return "[ERROR: " + e + "]\n"; }
	} else {
		if (t=="string"&&x=="") 
			x = '(empty)';
		r += "(" + t + ") " + x + "\n";
	}
	return r;

},

myConsole = function(){
	var args = cast(arguments);
	
	if (args.length>2){
		show(args.shift() +":\n"+ args.shift().replace(/(%[ds])/g, function(){
			return args.shift();
			})
		)
	}else{
		show(args.shift() +":\n"+ dump(args.shift()));
	}
};

if (window.loadFirebugConsole) { // since FireBug 1.2
	window.loadFirebugConsole();
} else if (!window.console) {
	window.console = {};
	var names = ["log", "debug", "info", "warn", "error"];
	for (var i in names){
		window.console[names[i]] = (function(name){return function(){
			myConsole.apply(null,[name].concat(cast(arguments)))
		}})(names[i]);
	}
}