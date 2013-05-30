// Profy colorer object
var profy_colorer = function() {

	/** @var */
	this.main_block_id		= "profy_colorer_main";
	/** @var */
	this.prev_block_id		= "profy_coloer_prev";
	/** @var */
	this.hex_block_id		= "profy_colorer_hex";
	/** @var */
	this.main_elm_obj		= null;
	/** @var */
	this.prev_elm_obj		= null;
	/** @var */
	this.hex_elm_obj		= null;
	/** @var */
	this.main_obj			= {};
	/** @var */
	this.is_created			= false;
	/** @var */
	this.onselect_func		= null;
	/** @var */
	this.main_block_width	= "210px";
	/** @var */
	this.main_block_height	= "145px";
	/** @var */
	this.main_table_style	= "border: 1px solid #000;cursor: crosshair;width:210px;height:145px;";
	/** @var */
	this.ua					= navigator.userAgent.toLowerCase();
	/** @var */
	this.is_ns				= this.ua.indexOf('gecko')>-1;

	/** @public */
	this.ns_event = function (e) {
		var st = this.main_elm_obj.style;
		st.position = "absolute";
		st.top	= e.clientY + "px";
		st.left	= e.clientX + "px";
		st.display = "";
		e.stopPropagation();
	}
	/** @public */
	this.show = function () {
		try {
			if (!this.is_ns) {
				var st = this.main_elm_obj.style;
				st.position = "absolute";
				st.top	= (window.event.y + document.body.scrollTop).toString(10) + "px";
				st.left	= (window.event.x + document.body.scrollLeft).toString(10) + "px";
				st.display = "";
			}
			this.hex_elm_obj.value = this.main_obj.value;
			this.prev_elm_obj.style.background = this.main_obj.value;
			this.hex_elm_obj.focus();
		} catch (e) {}
	}
	/** @public */
	this.hide = function () {
		this.main_elm_obj.style.display = "none";
	}
	/** @public */
	this.preview = function (obj) {
		this.prev_elm_obj.style.background = obj.style.background;
		var c = "",
		var color = obj.style.background.toUpperCase();
		if (this.is_ns) {
			try{
				var tmp = color.match(new RegExp("^RGB\\(([0-9]{1,3}), ([0-9]{1,3}), ([0-9]{1,3})\\).*",'i'));
				if (tmp) {
					var tmp1,str;
					for (var i = 1; i <= 3; i++) {
						eval("tmp1=" + tmp[i] + ";str=tmp1.toString(16);");
						c += (str.length == 1) ? "0" + str : str;
					}
				} else {
					c = "FFFFFF";
				}
				color = "#" + c;
			} catch(x) {
				color = "#FFFFFF";
			}
		}
		this.hex_elm_obj.value = color;
		this.hex_elm_obj.focus();
	}
	/** @public */
	this.select = function (obj) {
		try{
			this.main_obj.value = this.hex_elm_obj.value;
			eval(this.onselect_func + "('" + this.hex_elm_obj.value + "',this.main_obj);");
		} catch(x) {}
		this.hide();
	}
	/** @public */
	this.change = function (obj) {
		if (obj.value.length != 7) {
			return null;
		}
		var color;
		color = obj.value;
		if (color.match(new RegExp("^#[0-9A-F]{6}$", 'i'))) {
			this.prev_elm_obj.style.background = color;
		}
	}
	/** @public */
	this.create = function () {
		if (this.is_created) {
			return false;
		}
		try {
			var new_elm = document.createElement("DIV");
			new_elm.id = this.main_block_id;
			document.body.appendChild(new_elm);
			new_elm.style.display	= "none";
			new_elm.style.width		= this.main_block_width;
			new_elm.style.height	= this.main_block_height;
	
			body = "";
			body += "<table border=0 cellpadding=0 cellspacing=0 style='" + this.main_table_style + "'>";
			colors = new Array();
			var i;
			var j;
			var color;
			var col	= 0;
			var col2 = 0;
			for (i = 0; i < 42; i++) {
				colors[i] = new Array();
				for (j = 0; j < 6; j++) {
					if (i == 0 || i == 2 || i == 21 || i == 23) {
						color = "000";
					} else if (i == 1) {
						color = "00" + col2.toString(16);
						color = color.substr(color.length-3);
						col2 += 0x333;
					} else if (i == 22) {
						color = (j ==0) ? "F00" : color;
						color = (j ==1) ? "0F0" : color;
						color = (j ==2) ? "00F" : color;
						color = (j ==3) ? "FF0" : color;
						color = (j ==4) ? "0FF" : color;
						color = (j ==5) ? "F0F" : color;
					} else {
						color = "00" + col.toString(16);
						color = color.substr(color.length - 3);
						if ((col & 0x0FF) == 0x0FF) col += 0x1fe;
						if ((col & 0x00F) == 0x00F) col += 0x01e;
						col += 0x003;
					}
					colors[i][j] = color;
				}
			}
			for (j = 0; j < 12; j++) {
				body += "<tr>";
				for (i = 0; i < 21; i++) {
					body += "<td width='10' height='10' style='background:#";
					if (j < 6) {
						color = colors[i][j];
					} else {
						color = colors[i + 21][j % 6];
					}
					for (col = 0; col < 6; col++) {
						body += color.charAt(Math.floor(col / 2));
					}
					body += "' onMouseOver='profy_colorer.preview(this)' onMouseDown='profy_colorer.select(this)'></td>";
				}
				body += "</tr>";
			}
			body += "<tr>" +
				"<td id='" + this.prev_block_id + "' height=25 colspan=14 style='background:#000;border: 1px solid #FFF; cursor:auto;'>&nbsp;</td>"+
				"<td height=25 colspan=7 align='center' valign='middle' bgcolor='#dadada'><form onSubmit='profy_colorer.select(profy_colorer.hex_elm_obj);return false;'><input id='" + this.hex_block_id + "' type=text size=7 maxlength=7 onChange='profy_colorer.change(this)' onKeyUp='profy_colorer.change(this)' onKeyPressed='profy_colorer.change(this)'></td></form>" +
				"</tr>" +
				"</table>";
	    
			new_elm.innerHTML = body;
	    
			this.main_elm_obj	= document.getElementById(this.main_block_id);
			this.prev_elm_obj	= document.getElementById(this.prev_block_id);
			this.hex_elm_obj	= document.getElementById(this.hex_block_id);
	    
			this.is_created = true;
		} catch (e) {}
	}
	/** @public */
	this.get = function (objid, obj, func_name) {
		if (!this.is_created) {
			this.create();
		}
		if (this.is_ns) {
			obj.addEventListener("click", this.ns_event, false);
		}
		var old_obj_id = this.main_obj;
		this.main_obj = document.getElementById(objid);
		// Hide colorer on second click
		if (old_obj_id && this.main_obj && this.main_obj == old_obj_id && this.main_elm_obj.style.display != "none") {
			return this.hide();
		}
		this.onselect_func = func_name;
		this.show();
		return false;
	}
}

	
// Singleton pattern (init only once)
if (typeof(profy_colorer) != "object") {
	var profy_colorer = new profy_colorer();
}