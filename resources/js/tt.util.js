function namespace(namespaceString) {
   var parts = namespaceString.split('.'),
       parent = window,
       currentPart = '';    
   for(var i = 0, length = parts.length; i < length; i++) {
       currentPart = parts[i];
       parent[currentPart] = parent[currentPart] || {};
       parent = parent[currentPart];
   }
   return parent;
}

var TT = namespace('tt.util');


TT.validName = /^([a-z0-9A-Z])*$/;
TT.validNumber = /^([0-9])*$/;
	
TT.isValidEmail = function(email) {
	if(email.indexOf("@") < 0){
		return false;
	}if(email.indexOf("\.") < 0){
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

TT.confirmDelete = function(str, multiSelectable) {
	if(multiSelectable) {
		if(!TT.isCbxSelected()) {
			alert('Please select the '+str+' to be deleted');
			return false;
		}
	}
	return (confirm("Are you sure you want to delete selected " + str + "?"));
}

TT.confirmUserDelete = function(str) {
	return (confirm("Please note that all data records including files uploaded by the users get deleted when you delete a user account this action is irreversible. \n\nAre you sure you want to delete user account and all related projects/tasks?"));
}

TT.confirmReset = function() {
	return (confirm("Do you want to reset password for selected user?"));
}

TT.validateSigninForm = function(form){
	if (form.signin_id.value == '') {
		alert ('Please enter a valid user name or email id! ');
		return false;
	}
	if (form.password.value == '') {
		alert ('Please enter the password!');
		return false;
	}
	return true;
}
	
TT.showBlock = function(ObjectID){
	var Object = document.getElementById(ObjectID);
	Object.style.display = "block";
}

TT.hideBlock = function(ObjectID){
	var Object = document.getElementById(ObjectID)
	Object.style.display = "none";
}

TT.validateAndSubmitProject = function(form) {	
	var file = document.getElementById('uploadedfile_1').value;
	if (form.name.value == 0) {
		alert ('Project title can not be blank!');
		return false;
	}
	if (form.project_description.value == 0) {
		alert ('Project description can not be blank!');
		return false;
	}	
	if (confirm("Do you want to proceed with submission?")) {
		document.getElementById('project_upload_message').style.display = 'block';
		if(file != ''){
		document.getElementById('message').style.display = 'block';
		}
		return true;
	}
	return false;
}

	
TT.validateAndSubmitTask = function(form) {	
	var file = document.getElementById('uploadedfile_1').value;
	if (form.name.value == 0) {
		alert ('Task title can not be blank!');
		return false;
	}
	if (form.task_description.value == 0) {
		alert ('Task description can not be blank!');
		return false;
	}	
	if (confirm("Do you want to proceed with submission?")) {
		
		document.getElementById('task_upload_message').style.display ='block';
		if(file != ''){
		document.getElementById('message').style.display = 'block';
		}
		return true;
	}
	return false;
}

TT.validateProjectDescription = function(form) {	
	var file = document.getElementById('uploadedfile_1').value;
	var description = document.getElementById('description').value;
	if (description == '') {
		alert ('Please enter description.');
		return false;
	}	
	if (confirm("Do you want to proceed with submission?")) {
		document.getElementById('validate_project_upload_message').style.display='block';
		if(file != ''){
		document.getElementById('message').style.display = 'block';
		}
		return true;
	}
	return false;	
}


TT.validateTaskComments = function(form) {
	var comments = document.getElementById('comments').value;
	var file = document.getElementById('uploadedfile_1').value;
	if (file == '' && comments == '' )
	{	
		alert('Comments or  attachments are required!');
		return false;
	}
	if (confirm("Do you want to proceed with submission?")) {
		document.getElementById('validate_task_upload_message').style.display='block';
		if(file != ''){
		document.getElementById('message').style.display = 'block';
		}
		return true;
	}	
	return false;	
}


TT.validateIssueComments = function(form) {
	var comments = document.getElementById('comments').value;
	var file = document.getElementById('uploadedfile_1').value;
	if (file == '' && comments == '' )
	{	
		alert('Comments or  attachments are required!');
		return false;
	}
	if (confirm("Do you want to proceed with submission?")) {
		document.getElementById('validate_task_upload_message').style.display='block';
		if(file != ''){
		document.getElementById('message').style.display = 'block';
		}
		return true;
	}	
	return false;	
}

	
TT.confirmOpenIssue = function(){
	return (confirm("Are you sure you want to reopen this issue"));
}

TT.confirmPromoteIssue = function(){
	return (confirm("Are you sure you want to promote  issue as a task"));
}

TT.validateRegistrationInputs  = function(form) {

	if (form.email.value == '') {
		alert ('Please enter a valid email address!');
		return false;
	}
	if (!TT.isValidEmail(form.email.value)) {
		alert ('Email address not in correct format!');
		return false;
	}
	if (form.first_name.value == '') {
		alert ('First name can not be blank!'); /* Abhilash 26-10-13 */
		return false;
	}
	if (!TT.validName.test(form.first_name.value)) {
		alert ('First name should have alpha-numeric characters only!');
		return false;
	}
	if (form.signin_id.value == '') {
		alert ('Login name can not be blank!');		/* Abhilash 26-10-13 */
		return false;
	}
	if (!TT.validName.test(form.signin_id.value)) {
		alert ('Login name should have alpha-numeric characters only!');
		return false;
	}	
	if (form.signin_id.value.length < 3) {
		alert ('Login name field must be at least 3 characters in length!');
		return false;
	}
	if (form.password.value == '') {
		alert ('Please enter a valid password!');
		return false;
	}
	if (form.password.value.length < 5) {
		alert ('Password field must be at least 5 characters in length!');
		return false;
	}
	if (form.password.value !== form.password_repeat.value) {
		alert ('Passwords do not match, please try again!');
		return false;
	}
	return true;
}

TT.validateEmailInputs = function(form) {
	if (form.email.value == '') {
		alert ('Please enter a valid email address!');
		return false;
	}
	if (!TT.isValidEmail(form.email.value)) {
		alert ('Email address not in correct format!');
		return false;
	}
	return true;
}

TT.validatePasswordInputs = function(form) {
	if (form.password_old.value == '') {
		alert ('Please enter a valid current password!');
		return false;
	}
	if (form.password_new.value == '') {
		alert ('Please enter a valid new password!');
		return false;
	}
	if (form.password_new.value.length < 5) {
		alert ('New password must be at least 5 characters in length!');
		return false;
	}
	if (form.password_new.value !== form.password_new_repeat.value) {
		alert ('New passwords do not match, please try again!');
		return false;
	}	
	return true;
}


TT.validateProfileInputs = function(form) {
	if (form.email.value == '') {
		alert ('Please enter a valid email address!');
		return false;
	}
	if (!TT.isValidEmail(form.email.value)) {
		alert ('Email address not in correct format!');
		return false;
	}
	if (form.first_name.value == '') {
		alert ('First name can not be blank!');  /* Abhilash 26-10-13 */
		return false;
	}
	if (!TT.validName.test(form.first_name.value)) {
		alert ('First name should have alpha-numeric characters only!');
		return false;
	}
	if (form.signin_id !== undefined  && form.signin_id !== null && form.signin_id.value == '') {
		alert ('Login name field can not be blank!');
		return false;
	}
	if (!TT.validName.test(form.signin_id.value)) {
		alert ('Login name should have alpha-numeric characters only!');
		return false;
	}	
	if (form.signin_id.value.length < 3) {
		alert ('Login name field must be at least 3 characters in length!');
		return false;
	}
	return true;
}


TT.showProfileDetails = function(objectId){
	var profile_tab = document.getElementById('profile_tab');
	var password_tab = document.getElementById('password_tab');
		
	if(objectId == 'change_password'){
		document.getElementById('user_profile').style.display='none';
		document.getElementById('change_password').style.display='block';
		profile_tab.style.background='none';
		profile_tab.style.color='#333333';
		password_tab.style.background='#666666';
		password_tab.style.color='#ffffff';
		return;
	}
	if(objectId == 'user_profile'){
		document.getElementById('change_password').style.display='none';
		document.getElementById('user_profile').style.display='block';
		password_tab.style.background='none';
		password_tab.style.color='#333333';
		profile_tab.style.background='#666666';
		profile_tab.style.color='#ffffff';
		return;
	}
	return true;		
}

TT.setToMessageUserId = function(target_user_id) {
	var active_users =  document.getElementById('active_users');
	if (active_users !== null && active_users !== undefined) {
		for (var i = 0; i < active_users.options.length; i++) {
			if (active_users.options[i].value == target_user_id ) {
				active_users.options[i].selected  = true;
			}
			else {
				active_users.options[i].selected  = false;
			}
		}
	}	
	var comments = document.getElementById("comments"); 
	comments.focus(); 
}

TT.validateUserSettingsValues = function(form) {
	if (form.utmr.value == '' || form.utmr.value <=0 || !TT.validNumber.test(form.utmr.value)) {
		alert ('Please insert valid number for users per page');
		return false;
	}	
	if (form.ppp.value == '' || form.ppp.value <=0 || !TT.validNumber.test(form.ppp.value)) {
		alert ('Please insert valid number for projects per page');
		return false;
	}
	if (form.tcpp.value == '' || form.tcpp.value <=0 || !TT.validNumber.test(form.tcpp.value)) {
		alert ('Please insert valid number for task comments per page');
		return false;
	}	
	if (form.mdbi.value == '' || form.mdbi.value <=0 || !TT.validNumber.test(form.mdbi.value)) {
		alert ('Please insert valid number for maximum records per block in dashboard');
		return false;
	}		
	if (form.mmpp.value == '' || form.mmpp.value <=0 || !TT.validNumber.test(form.mmpp.value)) {
		alert ('Please insert valid number for maximum messages per page');
		return false;
	}	
	return true;
}

TT.validateSettingsValues = function(form) {	
	if (form.from_email_address.value == '') {
		alert ('Please enter a valid from email address!');
		return false;
	}
	if (!TT.isValidEmail(form.from_email_address.value)) {
		alert ('From email address not in correct format!');
		return false;
	}
	if (form.company_name.value == '') {
		alert ('Company Name can not be blank!');
		return false;
	}
	return true;
}


TT.upload_count =1;
TT.showUploadBlock = function(objectId){
	var object=document.getElementById(objectId);
	TT.upload_count =TT.upload_count+1;
	var id= "upload_block_"+TT.upload_count;
	var object2 =document.getElementById(id);
	object2.style.display ="block";	
	if(TT.upload_count >=6){
		object.style.display="none";
	}
	return true;
}

TT.validateAndSubmitProjectTitle = function(form) {	
	if (form.name.value == '') {
		alert ('Project title can not be blank!');
		return false;
	}
	if (confirm("Do you want to proceed with submission?")) {
		return true;
	}
	return false;
}
	
TT.showDeleteIcon =function (objectId){ 
	var iconId = 'delete_icon_'+objectId;
	var object = document.getElementById(iconId);
	object.style.display="block";
	return;
}

TT.deleteUpload =function (objectId){ 
	var element_id = 'upload_input_'+objectId; 
	var delete_icon_id = objectId;
	document.getElementById(element_id).innerHTML='<input id="uploadedfile_' + objectId + '" name="uploadedfile_' + objectId + '"  type="file"' + 'onchange="tt.util.showDeleteIcon(' + delete_icon_id + ')" />';
	var iconId = 'delete_icon_'+objectId;
	var object2 = document.getElementById(iconId);
	object2.style.display='none';
	return;
}


TT.arrow = 'right';
TT.morethemecolors = function (objectId,arrow_img,img_path){
	var object = document.getElementById(objectId);
	if(object.style.display == "block"){
		object.style.display = "none";
	}else if(object.style.display = "none"){
		object.style.display = "block";
	}
	
	var arrow_img_object = document.getElementById(arrow_img);
	if(TT.arrow == 'right'){
		arrow_image = 'left';
	}
	else if(TT.arrow == 'left'){
		arrow_image = 'right';
	}
	arrow_img_path = img_path+arrow_image+'_arrow.png';		
	arrow_img_object.innerHTML = '<img src="'+arrow_img_path+'" width="10" height="8" alt="more_theme_colors" title="More Theme Colors" />';
	TT.arrow = arrow_image;
	return;		
}

TT.moreusersettings = function (objectId,arrow_img,img_path){
	var object = document.getElementById(objectId);	
	if(object.style.display = "none"){
		object.style.display = "block";			
		var arrow_img_object = document.getElementById(arrow_img);
		arrow_img_path = img_path+'left_arrow.png';	
		arrow_img_object.innerHTML = '<img src="'+arrow_img_path+'" width="10" height="8" alt="more_user_settings" title="More User Settings" />';
	}	
	return;		
}

TT.hidemoreusersettings = function (objectId,arrow_img,img_path){
	var object = document.getElementById(objectId);	
	if(object.style.display == "block"){
		object.style.display = "none";
		var arrow_img_object = document.getElementById(arrow_img);
		arrow_img_path = img_path+'right_arrow.png';	
		arrow_img_object.innerHTML = '<img src="'+arrow_img_path+'" width="10" height="8" alt="more_user_settings" title="More User Settings" />';
	}	
	return;		
}
	
TT.validateTaskComment = function(form) {
	if(form.comment.value == ''|| uploadedfile_1.value == '') {
		alert('Please select file or enter comments');
		return false;
	}
	return true;
} 
	
	
TT.validateAndSubmitCopyTask = function(form){
	if(form.new_project_id.value == ''){
		alert('Please select destination project to which the task is to be copied! ');
		return false;
	}
	return true;
}

TT.validateSearch = function(form) {
	if(form.search_text.value == '' || form.search_text.value == 'Search'){
		alert('Please enter keyword to search');
		return false;
	}
	
	return true;
}

TT.confirmChangeIcon = function(form){
	if(form.uploaded_icon.value == ''){
		alert('Please provide an image to upload');
		return false;
	}
	else{
		msg = confirm('This action may delete the previously added image. Do you really want to change the image? ');
		if(msg = false){
			return false;
		}	
	}
	return true;
}


TT.confirmUnAssign = function(team_name){
	return (confirm("Are you sure you want to unassign the task for" + team_name + "?"));
}

TT.confirmUnAssignIssue = function(team_name) {
	return (confirm("Are you sure you want to remove " + team_name + " from issue tracking team?" ));
}
TT.validateAndSubmitIssue = function(form) {
	var file = document.getElementById('uploadedfile_1').value;	
	if (form.title.value == '') {
		alert ('Issue title can not be blank!');
		return false;
	}
	if (form.issue_description.value == '') {
		alert ('Issue description can not be blank!');
		return false;
	}	
	if (confirm("Do you want to proceed with submission?")) {
		document.getElementById('issue_upload_message').style.display = 'block';
		if(file != ''){
		document.getElementById('message').style.display = 'block';
		}
		return true;
	}
	return false;
}

TT.submitFilter = function(form,url){
	var project_id = form.value;
	return window.location = url+'/project_id/'+project_id;
}

TT.validateAddNewUser = function(form){
	if(form.team_id.value == ''){
		alert('Please select a user');
		return false;
	}
	return true;
}

TT.submitIssuesFilter = function(form,project_id,url){
	var issue_status = form.value;
	return window.location = url+'/project_id/'+project_id+'/status/'+issue_status;
}	

TT.submitProjectIssuesFilter = function(form,url) {
	var issue_status = form.value;
	return window.location = url+'/issue_status/'+issue_status;
}

TT.submitTaskFilter = function(form,url){
	var task_status = form.value;
	return window.location = url+'/task_status/'+task_status;
}
TT.submitPriorityFilter = function(form,url){
	var priority = form.value;
	return window.location = url+'/priority/'+priority;
}


TT.enableUserselection = function(object) {
	document.getElementById(object).style.display = 'block';
	var obj = document.getElementById(object);
}

TT.disableUserselection = function(object) {
	document.getElementById(object).selectedIndex = 0;
	document.getElementById(object).style.display = 'none';
}
TT.toggleCbxes = function (object) {
	var cbs = document.getElementsByTagName('input');
	
	  for(var i=0; i < cbs.length; i++) {
	    if(cbs[i].type == 'checkbox') {
	      cbs[i].checked = object.checked;
	    }
	  }
}
TT.isCbxSelected = function () {
	var cbs = document.getElementsByTagName('input');
	
	  for(var i=0; i < cbs.length; i++) {
	    if(cbs[i].type == 'checkbox') {
	       if(cbs[i].checked) {
	    	   return true;
	       }
	    }
	  }
	  return false;
}
TT.preview = function (url,ObjectID,closeImg)
{
	object = document.getElementById(ObjectID);
	object.style.display = "block";
	object.innerHTML = "<iframe src=" + url + " width='900' height='400' allowtransparency='true' style='position:absolute;z-index:1;' ></iframe>"
	closeObj = document.getElementById(closeImg);
	closeObj.style.display = "block";
}
TT.hidePreview = function (ObjectID,closeImg)
{
	object = document.getElementById(ObjectID);
	object.style.display = "none";
	closeObj = document.getElementById(closeImg);
	closeObj.style.display = "none";
}
/************ megha ******************/
TT.moremenus = function (objectId,arrow_img,img_path){
	var object = document.getElementById(objectId);	
	if(object.style.display = "none"){
		object.style.display = "block";			
		var arrow_img_object = document.getElementById(arrow_img);
		arrow_img_path = img_path+'left_arrow.png';	
		arrow_img_object.innerHTML = '<img src="'+arrow_img_path+'" width="10" height="8" alt="more_theme_colors" title="More Theme Colors" />';
	}	
	return;		
}

TT.hidemoremenus = function (objectId,arrow_img,img_path){
	var object = document.getElementById(objectId);	
	if(object.style.display == "block"){
		object.style.display = "none";
		var arrow_img_object = document.getElementById(arrow_img);
		arrow_img_path = img_path+'right_arrow.png';	
		arrow_img_object.innerHTML = '<img src="'+arrow_img_path+'" width="10" height="8" alt="more_theme_colors" title="More Theme Colors" />';
	}	
	return;		
}
/**********************************/		


TT.toogleSearch = function (overlayId ){
	
	var overlay =  document.getElementById(overlayId);
	overlay.style.display = (overlay.style.display == 'block') ? 'none' : 'block';
	
	return;		
}
