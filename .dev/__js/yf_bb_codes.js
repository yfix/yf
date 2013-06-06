// Main bb codes object
var yf_bb_codes = function() {

	// Current user agent
	this.ua					= navigator.userAgent.toLowerCase();
	// Check if this is Opera
	this.is_opera			= this.ua.indexOf("opera") > -1;
	// Check if this is Mozilla
	this.is_moz				= this.ua.indexOf('gecko') >- 1;
	// Check if this is IE
	this.is_ie				= window.ActiveXObject ? true : false;
	// Internationalization array (object)
	this.I18N_VARS			= {};
	// Stack for opened bb tags (object)
	this.bb_tags_stack		= {};
	// Opened tags (object)
	this.opened_tags		= {};
	// Max message length
	this.message_max		= 0;
	// Link to the emoticons window
	this.emo_pop_link		= "";
	// Link to the bb codes help window
	this.bb_pop_link		= "";

	this.emoticon_image = new Array();
	this.emoticon_image[':rolleyes:'] = 'rolleyes.gif';
	this.emoticon_image['<_<'] = 'dry.gif';
	this.emoticon_image[':mellow:'] = 'mellow.gif';
	this.emoticon_image[':unsure:'] = 'unsure.gif';
	this.emoticon_image[':blink:'] = 'blink.gif';
	this.emoticon_image[':wacko:'] = 'wacko.gif';
	this.emoticon_image[':angry:'] = 'mad.gif';
	this.emoticon_image[':ph34r:'] = 'ph34r.gif';
	this.emoticon_image[':lol:'] = 'laugh.gif';
	this.emoticon_image[':huh:'] = 'huh.gif';
	this.emoticon_image[':wub:'] = 'wub.gif';
	this.emoticon_image['-_-'] = 'sleep.gif';
	this.emoticon_image['^_^'] = 'happy.gif';
	this.emoticon_image[':o'] = 'ohmy.gif';
	this.emoticon_image[':('] = 'sad.gif';
	this.emoticon_image[';)'] = 'wink.gif';
	this.emoticon_image[':)'] = 'smile.gif';
	this.emoticon_image[':P'] = 'tongue.gif';
	this.emoticon_image[':D'] = 'biggrin.gif';
	this.emoticon_image['B)'] = 'cool.gif';
	
	// Constructor
	this.init = function () {
		try {
			this.I18N_VARS		= BB_CODES_I18N_VARS;
			this.emo_pop_link	= BB_CODES_EMO_POP_LINK;
			this.bb_pop_link	= BB_CODES_BB_POP_LINK;
			this.message_max	= !isNaN(BB_CODES_MAX_LENGTH) ? BB_CODES_MAX_LENGTH : 0;
			this.message_max	= parseInt(this.message_max);
			if (this.message_max < 0) {
				this.message_max = 0;
			}
		} catch (x) {};
	}

	// Display help box contents (fired by onmouseover etc)
	this.hstat = function (cmd_name, unique_id) {
		try {
			if (!this.I18N_VARS["help_" + cmd_name]) {
				return;
			} else {
				document.getElementById("helpbox_" + unique_id).value = this.i18n("help_" + cmd_name);
			}
		} catch (x) {}
	}

	// Set the number of tags open box
	this.cstat = function (unique_id) {
		try {
			// Create required arrays
			if (typeof(this.bb_tags_stack[unique_id]) == "undefined") {
				this.bb_tags_stack[unique_id] = [];
			}
			var c = this.stack_size(unique_id);
			if ((c < 1) || (c == null)) {
				c = 0;
			}
			if (!this.bb_tags_stack[unique_id][0]) {
				c = 0;
			}
			document.getElementById("tag_count_" + unique_id).value = c;
		} catch (x) {}
	}

	// SIMPLE TAGS (such as B I U, etc)
	this.simple_tag = function (thetag, unique_id) {
		try {
			// Create required arrays
			if (typeof(this.opened_tags[unique_id]) == "undefined") {
				this.opened_tags[unique_id] = {};
			}
			if (typeof(this.opened_tags[unique_id][thetag]) == "undefined") {
				this.opened_tags[unique_id][thetag] = 0;
			}
			if (typeof(this.bb_tags_stack[unique_id]) == "undefined") {
				this.bb_tags_stack[unique_id] = [];
			}
			var tag_open = this.opened_tags[unique_id][thetag];
			if (tag_open == 0) {
				if (this.do_insert("[" + thetag + "]", "[/" + thetag + "]", true, unique_id)) {
					this.opened_tags[unique_id][thetag] = 1;
					// Change the button status
					document.getElementById(thetag + "_" + unique_id).value += '*';
					this.push_stack(unique_id, thetag);
					this.cstat(unique_id);
					this.hstat('click_close', unique_id);
				}
			} else {
				// Find the last occurance of the opened tag
				var lastindex = 0;
				for (i = 0 ; i < this.bb_tags_stack[unique_id].length; i++) {
					if (this.bb_tags_stack[unique_id][i] == thetag) {
						lastindex = i;
					}
				}
				// Close all tags opened up to that tag was opened
				while (this.bb_tags_stack[unique_id][lastindex]) {
					var tag_remove = this.pop_stack(unique_id);
					this.do_insert("[/" + tag_remove + "]", "", false, unique_id);
					// Change the button status
					if ((tag_remove != 'FONT') && (tag_remove != 'SIZE') && (tag_remove != 'COLOR')) {
						document.getElementById(tag_remove + "_" + unique_id).value = " " + tag_remove + " ";
						this.opened_tags[unique_id][tag_remove] = 0;
					}
				}
				this.cstat(unique_id);
			}
		} catch (x) {}
	}

	// GENERAL INSERT FUNCTION
	this.do_insert = function (ib_tag, ib_cls_tag, is_single, unique_id) {
		var p_obj = document.getElementById(unique_id);

		var is_close = false;
		// It's IE!
		if (this.is_ie) {
			if (p_obj.isTextEdit) {
				p_obj.focus();
				var sel = document.selection;
				var rng = sel.createRange();
				rng.colapse;
				if ((sel.type == "Text" || sel.type == "None") && rng != null) {
					if (ib_cls_tag != "" && rng.text.length > 0) {
						ib_tag += rng.text + ib_cls_tag;
					} else if (is_single) {
						is_close = true;
					}
					rng.text = ib_tag;
				}
			} else {
				if (is_single) {
					is_close = true;
				}
				p_obj.value += ib_tag;
			}
		}
		// It's MOZZY!
		else if (p_obj.selectionEnd) { 
			var ss = p_obj.selectionStart;
			var st = p_obj.scrollTop;
			var es = p_obj.selectionEnd;
			if (es <= 2) {
				es = p_obj.textLength;
			}
			var start  = (p_obj.value).substring(0, ss);
			var middle = (p_obj.value).substring(ss, es);
			var end	= (p_obj.value).substring(es, p_obj.textLength);
			// text range?
			if (p_obj.selectionEnd - p_obj.selectionStart > 0) {
				middle = ib_tag + middle + ib_cls_tag;
			} else {
				middle = ib_tag + middle;
				if (is_single) {
					is_close = true;
				}
			}
			p_obj.value = start + middle + end;
			var cpos = ss + (middle.length);
			p_obj.selectionStart = cpos;
			p_obj.selectionEnd   = cpos;
			p_obj.scrollTop	  = st;
		}
		// It's CRAPPY!
		else {
			if (is_single) {
				is_close = true;
			}
			p_obj.value += ib_tag;
		}
		p_obj.focus();
		return is_close;
	}

	// Close all tags
	this.close_all = function (unique_id) {
		try {
			var p_obj = document.getElementById(unique_id);
			if (typeof(this.bb_tags_stack[unique_id]) == "undefined") {
				this.bb_tags_stack[unique_id] = [];
			}
			if (this.bb_tags_stack[unique_id][0]) {
				while (this.bb_tags_stack[unique_id][0]) {
					tag_remove = this.pop_stack(unique_id);
					p_obj.value += "[/" + tag_remove + "]";
					// Change the button status, Ensure we're not looking for FONT, SIZE or COLOR as these
					// buttons don't exist, they are select lists instead.
					if ((tag_remove != 'FONT') && (tag_remove != 'SIZE') && (tag_remove != 'COLOR')) {
						// Clean "*" on the buttons
						document.getElementById(tag_remove + "_" + unique_id).value = tag_remove;
						this.opened_tags[unique_id][tag_remove] = 0;
					}
				}
			}
			// Ensure we got them all
			document.getElementById("tag_count_" + unique_id).value = 0;
			this.bb_tags_stack[unique_id] = [];
			p_obj.focus();
		} catch (x) {}
	}

	// EMOTICONS
	this.emoticon = function (the_smilie, unique_id) {
		try {
			var p_obj = document.getElementById(unique_id);
			this.do_insert(" " + the_smilie + " ", "", false, unique_id);
		} catch (x) {
			var oEditor = FCKeditorAPI.GetInstance('text2');
			//oEditor.InsertHtml(" " + the_smilie + " ");

			if(typeof(this.emoticon_image[the_smilie]) !== "undefined"){
				oEditor.InsertHtml(' <img alt="" src="'+PATH_TO_IMAGE+this.emoticon_image[the_smilie]+'" /> ');
			}

		}
	}

	// ADD CODE
	this.add_code = function (new_code, unique_id) {
		try {
			var p_obj = document.getElementById(unique_id);
			p_obj.value += new_code;
			p_obj.focus();
		} catch (x) {}
	}

	// ALTER FONT
	this.alter_font = function (theval, thetag, unique_id) {
		try {
			if (theval == 0) return;
			if (this.do_insert("[" + thetag + "=" + theval + "]", "[/" + thetag + "]", true, unique_id)) {
				this.push_stack(unique_id, thetag);
			}
			document.getElementById("f_family_" + unique_id).selectedIndex  = 0;
			document.getElementById("f_size_" + unique_id).selectedIndex  = 0;
			document.getElementById("f_color_" + unique_id).selectedIndex = 0;
			this.cstat(unique_id);
		} catch (x) {}
	}

	// List tag
	this.tag_list = function (unique_id) {
		try {
			var listvalue	= "init";
			var thelist		= "";
			while ((listvalue != "") && (listvalue != null)) {
				listvalue = prompt(this.i18n("list_prompt"), "");
				if ((listvalue != "") && (listvalue != null)) {
					thelist = thelist + "[*]" + listvalue + "\n";
				}
			}
			if (thelist != "") {
				this.do_insert("[LIST]\n" + thelist + "[/LIST]\n", "", false, unique_id);
			}
		} catch (x) {}
	}

	// URL tag
	this.tag_url = function (unique_id) {
		try {
			var found_errors = '';
			var enter_url   = prompt(this.i18n("text_enter_url"), "http://");
			var enter_title = prompt(this.i18n("text_enter_url_name"), "My Webpage");
			if (!enter_url) {
				found_errors += " " + this.i18n("error_no_url");
			}
			if (!enter_title) {
				found_errors += " " + this.i18n("error_no_title");
			}
			if (found_errors) {
				alert("Error!" + found_errors);
				return;
			}
			this.do_insert("[URL=" + enter_url + "]" + enter_title + "[/URL]", "", false, unique_id);
		} catch (x) {}
	}

	// Image tag
	this.tag_image = function (unique_id) {
		try {
			var found_errors = '';
			var _def_url = 'http://';
			var enter_url   = prompt(this.i18n("text_enter_image"), _def_url);
			if (!enter_url || enter_url == _def_url) {
				found_errors += " " + this.i18n("error_no_url");
			}
			if (found_errors) {
				alert("Error!" + found_errors);
				return;
			}
			this.do_insert("[IMG]" + enter_url + "[/IMG]", "", false, unique_id);
		} catch (x) {}
	}

	// Youtube tag
	this.tag_youtube = function (unique_id) {
		try {
			var found_errors = '';
			var _def_url = 'http://www.youtube.com/';
			var enter_url   = prompt(this.i18n("text_enter_youtube"), _def_url);
			if (!enter_url || enter_url == _def_url) {
				found_errors += " " + this.i18n("error_no_url");
			}
			if (found_errors) {
				alert("Error!" + found_errors);
				return;
			}
			enter_url = enter_url.replace(/watch\?v=/i, "v/");
			this.do_insert("[YOUTUBE]" + enter_url + "[/YOUTUBE]", "", false, unique_id);
		} catch (x) {}
	}

	// Email tag
	this.tag_email = function (unique_id) {
		try {
			var email_address = prompt(this.i18n("text_enter_email"), "");
			if (!email_address) { 
				alert(this.i18n("error_no_email"));
				return; 
			}
			this.do_insert("[EMAIL]" + email_address + "[/EMAIL]", "", false, unique_id);
		} catch (x) {}
	}

	// Insert attachment tag
	this.insert_attach_to_textarea = function (aid, unique_id) {
		try {
			this.do_insert("[attachmentid=" + aid + "]", "", false, unique_id);
		} catch (x) {}
	}

	// Check text length
	this.check_length = function (unique_id) {
		try {
			var p_obj = document.getElementById(unique_id);
			var message_length  = p_obj.value.length;
			var message  = "";
			if (this.message_max > 0){
				message = this.i18n("max_allowed_length") + " " + message_max + " " + this.i18n("characters") + ".";
			} else{
				message = "";
			}
			alert(message + this.i18n("you_have_used") + " " + message_length + " " + this.i18n("characters") + ".");
		} catch (x) {}
	}

	// Array: Get stack size
	this.stack_size = function (unique_id) {
		if (typeof(this.bb_tags_stack[unique_id]) == "undefined") {
			this.bb_tags_stack[unique_id] = [];
		}
		var the_array = this.bb_tags_stack[unique_id];
		for (i = 0 ; i < the_array.length; i++) {
			if ((the_array[i] == "") || (the_array[i] == null) || (the_array == 'undefined')) {
				return i;
			}
		}
		return the_array.length;
	}

	// Array: Push stack
	this.push_stack = function (unique_id, new_val) {
		if (typeof(this.bb_tags_stack[unique_id]) == "undefined") {
			this.bb_tags_stack[unique_id] = [];
		}
		var array_size	= this.stack_size(unique_id);
		this.bb_tags_stack[unique_id][array_size] = new_val;
	}

	// Array: Pop stack
	this.pop_stack = function (unique_id) {
		if (typeof(this.bb_tags_stack[unique_id]) == "undefined") {
			this.bb_tags_stack[unique_id] = [];
		}
		var array_size = this.stack_size(unique_id);
		var the_val = this.bb_tags_stack[unique_id][array_size - 1];
		delete this.bb_tags_stack[unique_id][array_size - 1];
		return the_val;
	}

	// Translate vars method
	this.i18n	= function (text) {
		var text_l = text.toLowerCase();
		// Try to translate given text
		if (typeof(this.I18N_VARS[text_l]) != "undefined") {
			text = this.I18N_VARS[text_l];
		}
		return text;
	}

	// Create Popup window with emoticons
	this.emo_pop = function (unique_id) {
		window.open(this.emo_pop_link, this.i18n("Legends"), "width=250,height=500,resizable=yes,scrollbars=yes");
	}

	// Create Popup window with BB Codes help
	function bbc_pop(){
		window.open(this.bb_pop_link, this.i18n("Legends"), "width=700,height=500,resizable=yes,scrollbars=yes");
	}
}
	
// Singleton pattern (init only once)
if (isNaN(yf_bb_codes)) {
	var yf_bb_codes = new yf_bb_codes();
	yf_bb_codes.init();
}