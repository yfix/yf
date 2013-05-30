// Array: Get stack size
function stacksize(thearray) {
	for (i = 0 ; i < thearray.length; i++) {
		if ((thearray[i] == "") || (thearray[i] == null) || (thearray == 'undefined')) {
			return i;
		}
	}
	return thearray.length;
}
// Array: Push stack
function pushstack(thearray, newval) {
	arraysize = stacksize(thearray);
	thearray[arraysize] = newval;
}
// Array: Pop stack
function popstack(thearray) {
	arraysize = stacksize(thearray);
	theval = thearray[arraysize - 1];
	delete thearray[arraysize - 1];
	return theval;
}
// Get element by id
function my_getbyid(id) {
	item_obj = null;
	if (document.getElementById)	item_obj = document.getElementById(id);
	else if (document.all)			item_obj = document.all[id];
	else if (document.layers)		item_obj = document.layers[id];
	return item_obj;
}
// Show/hide toggle
function toggleview(id) {
	if (!id) return;
	if (item_obj = my_getbyid(id)) {
		if (item_obj.style.display == "none") {
			item_obj.style.display = "";
		} else {
			item_obj.style.display = "none";
		}
	}
}
// Hide / Unhide menu elements
function ShowHide(id1, id2) {
	if (id1 != '') toggleview(id1);
	if (id2 != '') toggleview(id2);
}

// Topics moderation method (view_forum and view_topic)
function toggle_id(pid) {
	// Got a number?
	if (isNaN(pid)) {
		return false;
	}
	// Define required arrays
	saved = new Array();
	clean = new Array();
	add   = 1;
	// Get form info
	tmp = document.forms["modform"].selected_ids.value;
	saved = tmp.split(",");
	// Remove bit if exists
	for(i = 0 ; i < saved.length; i++) {
		if (saved[i] != "")	{
			if (saved[i] == pid) {
				 add = 0;
			} else {
				clean[clean.length] = saved[i];
			}
		}
	}
	// Add?
	if (add) {
		clean[clean.length] = pid;
	}
	newvalue = clean.join(',');
	document.forms["modform"].selected_ids.value = newvalue;
	return false;
}
// Topics moderation method (view_forum and view_topic)
function check_delete() {
	if (!moderator_form.selected_ids.value) {
		return false;
	}
	isDelete = moderator_form.t_act.options[moderator_form.t_act.selectedIndex].value;
	if (isDelete == 'delete') {
		return formCheck = confirm('Are you sure?');
	}
}