/**
 * Only enable Javascript functionality if all required features are supported.
 */
function isJsEnabled() {
	if (typeof document.jsEnabled == 'undefined') {
		// Note: ! casts to boolean implicitly.
		document.jsEnabled = !(
			!document.getElementsByTagName	||
			!document.createElement			||
			!document.createTextNode		||
			!document.documentElement		||
			!document.getElementById);
	}
	return document.jsEnabled;
}

// Global Killswitch on the <html> element
if (isJsEnabled()) {
	document.documentElement.className = 'js';
}

/**
 * Adds a function to the window onload event
 */
function addLoadEvent(func) {
	var oldOnload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	}
	else {
		window.onload = function() {
			oldOnload();
			func();
		}
	}
}

/**
 * Retrieves the absolute position of an element on the screen
 */
function absolutePosition(el) {
	var sLeft = 0, sTop = 0;
	var isDiv = /^div$/i.test(el.tagName);
	if (isDiv && el.scrollLeft) {
		sLeft = el.scrollLeft;
	}
	if (isDiv && el.scrollTop) {
		sTop = el.scrollTop;
	}
	var r = { x: el.offsetLeft - sLeft, y: el.offsetTop - sTop };
	if (el.offsetParent) {
		var tmp = absolutePosition(el.offsetParent);
		r.x += tmp.x;
		r.y += tmp.y;
	}
	return r;
};

function dimensions(el) {
	return { width: el.offsetWidth, height: el.offsetHeight };
}

/**
 * Returns true if an element has a specified class name
 */
function hasClass(node, className) {
	if (node.className == className) {
		return true;
	}
	var reg = new RegExp('(^| )'+ className +'($| )')
	if (reg.test(node.className)) {
		return true;
	}
	return false;
}

/**
 * Adds a class name to an element
 */
function addClass(node, className) {
	if (hasClass(node, className)) {
		return false;
	}
	node.className += ' '+ className;
	return true;
}

/**
 * Removes a class name from an element
 */
function removeClass(node, className) {
	if (!hasClass(node, className)) {
		return false;
	}
	// Replaces words surrounded with whitespace or at a string border with a space. Prevents multiple class names from being glued together.
	node.className = eregReplace('(^|\\s+)'+ className +'($|\\s+)', ' ', node.className);
	return true;
}

/**
 * Toggles a class name on or off for an element
 */
function toggleClass(node, className) {
	if (!removeClass(node, className) && !addClass(node, className)) {
		return false;
	}
	return true;
}

/**
 * Emulate PHP's ereg_replace function in javascript
 */
function eregReplace(search, replace, subject) {
	return subject.replace(new RegExp(search,'g'), replace);
}

/**
 * Removes an element from the page
 */
function removeNode(node) {
	if (typeof node == 'string') {
		node = $(node);
	}
	if (node && node.parentNode) {
		return node.parentNode.removeChild(node);
	}
	else {
		return false;
	}
}

/**
 * Prevents an event from propagating.
 */
function stopEvent(event) {
	if (event.preventDefault) {
		event.preventDefault();
		event.stopPropagation();
	}
	else {
		event.returnValue = false;
		event.cancelBubble = true;
	}
}

/**
 * Wrapper around document.getElementById().
 */
function $(id) {
	return document.getElementById(id);
}

if (isJsEnabled()) {
	addLoadEvent(textAreaAutoAttach);
}

function textAreaAutoAttach(event, parent) {
	if (typeof parent == 'undefined') {
		// Attach to all visible textareas.
		textareas = document.getElementsByTagName('textarea');
	}
	else {
		// Attach to all visible textareas inside parent.
		textareas = parent.getElementsByTagName('textarea');
	}
	var textarea;
	for (var i = 0; textarea = textareas[i]; ++i) {
//		if (hasClass(textarea, 'resizable')/* && !hasClass(textarea.nextSibling, 'grippie')*/) {
			if (typeof dimensions(textarea).width != 'undefined' && dimensions(textarea).width != 0) {
				new textArea(textarea);
			}
//		}
	}
}

function textArea(element) {
	var ta = this;
	this.element = element;
	this.parent = this.element.parentNode;
	this.dimensions = dimensions(element);

	// Prepare wrapper
	this.wrapper = document.createElement('div');
	this.wrapper.className = 'resizable-textarea';
	this.parent.insertBefore(this.wrapper, this.element);

	// Add grippie and measure it
	this.grippie = document.createElement('div');
	this.grippie.className = 'grippie';
	this.wrapper.appendChild(this.grippie);
	this.grippie.dimensions = dimensions(this.grippie);
	this.grippie.onmousedown = function (e) { ta.beginDrag(e); };

	// Set wrapper and textarea dimensions
	this.wrapper.style.height = this.dimensions.height + this.grippie.dimensions.height + 1 +'px';
	this.element.style.marginBottom = '0px';
	this.element.style.width = '100%';
	this.element.style.height = this.dimensions.height +'px';

	// Wrap textarea
	removeNode(this.element);
	this.wrapper.insertBefore(this.element, this.grippie);

	// Measure difference between desired and actual textarea dimensions to account for padding/borders
	this.widthOffset = dimensions(this.wrapper).width - this.dimensions.width;

	// Make the grippie line up in various browsers
	if (window.opera) {
		// Opera
		this.grippie.style.marginRight = '4px';
	}
	if (document.all && !window.opera) {
		// IE
		this.grippie.style.width = '100%';
		this.grippie.style.paddingLeft = '2px';
	}
	// Mozilla
	this.element.style.MozBoxSizing = 'border-box';

	this.heightOffset = absolutePosition(this.grippie).y - absolutePosition(this.element).y - this.dimensions.height;
}

textArea.prototype.beginDrag = function (event) {
	if (document.isDragging) {
		return;
	}
	document.isDragging = true;

	event = event || window.event;
	// Capture mouse
	var cp = this;
	this.oldMoveHandler = document.onmousemove;
	document.onmousemove = function(e) { cp.handleDrag(e); };
	this.oldUpHandler = document.onmouseup;
	document.onmouseup = function(e) { cp.endDrag(e); };

	// Store drag offset from grippie top
	var pos = absolutePosition(this.grippie);
	this.dragOffset = event.clientY - pos.y;

	// Make transparent
	this.element.style.opacity = 0.5;

	// Process
	this.handleDrag(event);
}

textArea.prototype.handleDrag = function (event) {
	event = event || window.event;
	// Get coordinates relative to text area
	var pos = absolutePosition(this.element);
	var y = event.clientY - pos.y;

	// Set new height
	var height = Math.max(32, y - this.dragOffset - this.heightOffset);
	this.wrapper.style.height = height + this.grippie.dimensions.height + 1 + 'px';
	this.element.style.height = height + 'px';

	// Avoid text selection
	stopEvent(event);
}

textArea.prototype.endDrag = function (event) {
	// Uncapture mouse
	document.onmousemove = this.oldMoveHandler;
	document.onmouseup = this.oldUpHandler;

	// Restore opacity
	this.element.style.opacity = 1.0;
	document.isDragging = false;
}