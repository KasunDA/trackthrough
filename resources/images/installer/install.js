function isValidEmail(email) {
	if(email.indexOf("@") < 0){
		return false;
	}else{
		var sub_text = email.split("@");
		var prefix = sub_text[0];
		if(prefix == ''){
	    	return false;
		}
		var suffix = sub_text[sub_text.length -1];
		if(suffix == ''){
			return false;
		}else if((suffix.indexOf("\.") <= 0)  || (suffix.indexOf("\.") == (email.length -1))){
			return false;
		}
	}
	return true;
		 
}

var validName = /^([a-z0-9A-Z])*$/;

function check_blank(form){	
	if(form.dbname.value == ''){
		alert("Please enter Database name.");
		return false;
	}	
	if(form.dbserver.value == ''){
		alert("Please enter Database server.");
		return false;
	}
	if(form.dbuser.value == ''){
		alert("Please enter Database username.");
		return false;
	}
	if(form.admin_username.value == ''){
		alert("Please enter Admin username.");
		return false;
	}
	if (!validName.test(form.admin_username.value)) {
		alert ('Admin username should have alpha-numeric characters only.');
		return false;
	}
	if (form.admin_username.value.length < 3) {
		alert ('Admin username field must be at least 3 characters in length.');
		return false;
	}
	if(form.admin_password.value == ''){
		alert("Please enter Admin password.");
		return false;
	}
	if (form.admin_password.value.length < 5) {
		alert ('Admin password field must be at least 5 characters in length.');
		return false;
	}
	if (form.admin_password.value !== form.admin_confirm_password.value) {
		alert ('Admin passwords do not match, please try again!');
		return false;
	}	
	if(form.admin_email_id.value != ''){
		if (!isValidEmail(form.admin_email_id.value)) {
			alert ('Admin email address not in correct format!');
			return false;
		}
	}
	return true;				
}

function highlightField(objectID){
	document.getElementById(objectID).style.border = "2px solid #6ea0d8";
	return false;
}

function normalField(objectID){
	document.getElementById(objectID).style.border = "1px solid #DDDDDD";
	return false;
}