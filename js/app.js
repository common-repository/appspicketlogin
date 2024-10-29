/**
 * Algorithm Constants
 */

var P_1024 = bigInt("E0A67598CD1B763BC98C8ABB333E5DDA0CD3AA0E5E1FB5BA8A7B4EABC10BA338FAE06DD4B90FDA70D7CF0CB0C638BE3341BEC0AF8A7330A3307DED2299A0EE606DF035177A239C34A912C202AA5F83B9C4A7CF0235B5316BFC6EFB9A248411258B30B839AF172440F32563056CB67A861158DDD90E6A894C72A5BBEF9E286C6B",16);
var Q_1024 = bigInt("E950511EAB424B9A19A2AEB4E159B7844C589C4F",16);
var G_1024  = bigInt("D29D5121B0423C2769AB21843E5A3240FF19CACC792264E3BB6BE4F78EDD1B15C4DFF7F1D905431F0AB16790E1F773B5CE01C804E509066A9919F5195F4ABC58189FD9FF987389CB5BEDF21B4DAB4F8B76A055FFE2770988FE2EC2DE11AD92219F0B351869AC24DA3D7BA87011A701CE8EE7BFE49486ED4527B7186CA4610A75",16);
var url = "https://mobile.appspicket.com/module.php/extendtwofactorauthentication/ipragsaml.php";
var baseurl = window.location.protocol + "//" + window.location.host + "/";
var ajaxurl = baseurl+"wp-admin/admin-ajax.php"; 
function signup(username, password,url, email, mobileno) {
	console.log("md5(password)", md5(password));
	var k2 = bigInt(md5(password), 16);
	console.log("k2", k2.toString());

	var k1 = bigInt.randBetween(bigInt(0), Q_1024);
	console.log("k1", k1.toString());
  
	var key = (k1.multiply(k2)).mod(Q_1024);
	console.log("key", key.toString());

    var pk = G_1024.modPow(key, P_1024);
    console.log("PK", pk.toString());  

    localStorage.setItem('uname', email);
    localStorage.setItem('PK', pk.toString());
    var deviceid = pk.toString();
    jQuery('#device_id').val(deviceid);
    localStorage.setItem('deviceId', deviceid);
    localStorage.setItem('k1', k1.toString());
    localStorage.setItem('email', email);
    
    jQuery(".loading_image").show();
    
    jQuery.ajax({
    	url: url,
    	data: {
    		uname : email,
    		password : password,
    		email: email,
    		mobileno: mobileno,
    		PK : pk.toString(),
    		step : 'signup',
    		deviceId : deviceid
    	},
    	type: 'post',
    	dataType: 'json',
    	cache: false
    })
    .done(function(data, textStatus, jqXHR) {
    	console.log(data);
    	if (data.status == false) {
    		jQuery(".loading_image").hide();
    		if(data.error == 'AuthenticationException'){
    			jQuery(".message").html("Authentication failed. Please sign-up again."); 
    		}else{
    			jQuery(".message").html(data.message); 
    		}
    	}
    	else {
    		jQuery('#confirm_otp').show();
    		localStorage.setItem('user_id', data.user_id);	
    		jQuery("#popupLogin").dialog();
    		jQuery(".loading_image").hide();
    	}
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
    	console.log('error: ' + textStatus);
    	jQuery(".loading_image").hide();
    	jQuery(".message").html("Error: " + textStatus);
    })
}

function login(username, password, url, form) {
	localStorage.setItem('uname', username);
	console.log("username", username);
	var l = bigInt.randBetween(bigInt(0), Q_1024);
	var com = G_1024.modPow(l, P_1024);
	localStorage.setItem('l', l.toString());
	localStorage.setItem('com', com.toString());
	var device_id = localStorage.getItem('deviceId');
	if(!device_id){
		jQuery(".message").html("Authentication failed. Please sign-up again."); 
		return false;
	}
	
	jQuery(".loading_image").show();
	jQuery.ajax({
		url: url,
		data: {
			uname : username,
			Com : com.toString(),
			step : 'step1',
			deviceId : localStorage.getItem('deviceId')
		},
		type: 'post',
		dataType: 'json',
	})
	.done(function(data, textStatus, jqXHR) {
		jQuery.each (data.values, function (key, val) {	
			localStorage.setItem(val.name, val.val);
		});
		if ('error' in data) {
			jQuery(".loading_image").hide();
			jQuery(".message").html("Authentication failed. Please sign-up again."); 
		}
        else {
        	var k2 = bigInt(md5(password), 16);
        	var k1 = bigInt(localStorage.getItem('k1'));
        	console.log("k1", k1.toString());
        	var key = (k1.multiply(k2)).mod(Q_1024);
        	console.log("key", key.toString());
			var l = bigInt(localStorage.getItem('l'));
			var ch = bigInt(localStorage.getItem('ch'));
			var s = l.add(ch.multiply(key)).mod(Q_1024);
			localStorage.setItem(s, s.toString());
            step_2(s.toString(), device_id, username, password, form);
		}
		
	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log('error: ' + textStatus);
		jQuery(".loading_image").hide();
		jQuery(".message").html("Error: " + textStatus);
	});
}

function step_2(s, device_id, username, password, form) {
	jQuery.ajax({
		url: url,
		data: {
			uname : username,
			s : s,
			step : 'step2',
			deviceId : device_id
		},
		type: 'post',
		dataType: 'json',
	})
	.done(function(data, textStatus, jqXHR) {
		console.log(data);
		if(data.status == true){
			jQuery("#user_pass").val(s+':'+device_id);
			form.submit();
    	}else{
    		jQuery(".message").html("Authentication failed.");
    		jQuery(".loading_image").hide();
    	}
		
		
	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log('error: ' + textStatus);
		jQuery(".loading_image").hide();
		jQuery(".message").html("Error: " + textStatus);
	});
	
}


function confirm_otp(otp,url){
	jQuery(".loading_image").show();
    jQuery.ajax({
    	url: url,
    	data: {
            user_id : localStorage.getItem('user_id'),
            uname : localStorage.getItem('uname'),
    		otp : otp,
    		step : 'confirm-otp',
    	},
    	type: 'post',
    	dataType: 'json',
    	cache: false
    })
    .done(function(data, textStatus, jqXHR) {
    	if (data.status == false) {
    		jQuery(".loading_image").hide();
    		jQuery(".message").html("Error: " + data.message); 
    	}
    	else {
    		create_system_users();
    		jQuery(".message").html(data.message);
    		jQuery("#popupLogin").dialog("close");
    		localStorage.setItem('deviceId', data.device_id);
    		jQuery(".loading_image").hide();
    		
    	}
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
    	console.log('error: ' + textStatus);
    	jQuery(".loading_image").hide();
    	jQuery(".message").html("Error: " + textStatus);
    })
}

function create_system_users(){
	var system_ajaxurl = jQuery('#ajaxurl').val();
	localStorage.setItem('ajaxurl',system_ajaxurl)
	var login_url = jQuery('#loginurl').val();
    jQuery.ajax({
    	url: system_ajaxurl,
    	data: {
    		action: "create_user",
    		uname : localStorage.getItem('uname'),
    		email : localStorage.getItem('email'),
    	},
    	type: 'POST',
    	dataType: 'json',
    	cache: false
    })
    .done(function(data, textStatus, jqXHR) {
    	if(data.status == true){
    		window.location.href = login_url;
    	}else{
    		
    	}
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
    	console.log('error: ' + textStatus);
    	jQuery(".message").html("error: " + textStatus);
    });
}

function check_exist_user(username, password, url, form){
	var user_email = '';
    jQuery.ajax({
    	url: localStorage.getItem('ajaxurl'),
    	data: {
    		action: "check_exist_user",
    		uname : username,
    	},
    	type: 'POST',
    	dataType: 'json',
    	cache: false
    })
    .done(function(data, textStatus, jqXHR) {
    	if(data.status == true){
    		user_email = data.email;
    		login(user_email,password,url, form);
    	}
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
    	console.log('error: ' + textStatus);
    	jQuery(".message").html("Error: " + textStatus);
    });
}

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

jQuery( document ).ready(function() {
	if(getUrlParameter('action') == "register"){
		jQuery('label[for="user_login"]').hide();
	}
	jQuery("#loginform").before('<p class="message">Login</p>');
	var image_url = jQuery("#pluginurl").val();
	image_url = image_url + "/images/ajax-loader.gif";
	jQuery("#registerform").before('<div class="loading_image" style="text-align: center;display:none"><img src="'+image_url+'"></div>');
    jQuery("#registerform").after('<div id="popupLogin" style="display:none;" title="Please provide verification code"><form  name="confirm-otp-form" id="confirm-otp-form" style="padding: 0;box-shadow: none;background: none;"><input type="text" name="otp" class="input" id="otp" placeholder="OTP"><div class="form-error" ></div><input type="submit" name="confirm_otp" id="confirm_otp" class="button button-primary button-large" value="Confirm verification code"></form></div>');
    jQuery('#registerform').validate({
        rules: {
        	user_login : {
                required: true
            },
            password : {
                required: true
            },
            user_email : {
                required: true,
                email:true,
            }
        },
        messages: {
        	user_login: {
                required: "Please provide username."
            },
            password: {
                required: "Please provide password."
            },
            user_email: {
                required: "Please provide email.",
                email: "Please provide valid email."
            },
        },
        errorPlacement: function (error, element) {
        	element.parent().after("<div class='form-error'></div>");
            error.appendTo(element.parent().next('.form-error'));
            element.parent().next().css("margin-bottom", "10px");
            element.parent().next().find('.error').css("color", "red");
        }
    });	
    
    jQuery('#registerform').on('submit', function(event) {
    	var email = jQuery("#user_email").val();
    	jQuery("#user_login").val(email);
    	var username = jQuery("#user_login").val();
	    var password = jQuery("#password").val();
	    var mobileno = jQuery("#mobileno").val();
		if (jQuery('#registerform').valid()) {
			signup(username, password,url, email, mobileno);			
		};
    	event.preventDefault();
    	return false;
    	
    });
    
    jQuery('#confirm-otp-form').on('submit', function(event){  // capture the click    
		var otp = jQuery("#otp").val();
		if (jQuery('#confirm-otp-form').valid()) {
                confirm_otp(otp, url);
         };
		 event.preventDefault();
		 return false;
    });
    
    jQuery('#confirm-otp-form').validate({
        rules: {
        	otp : {
                required: true
            }
        },
        messages: {
        	otp: {
                required: "Please provide verification code."
            },
        },
        errorPlacement: function (error, element) {
        	element.after("<div class='form-error'></div>");
            error.appendTo(element.next('.form-error'));
            element.next().find('.error').css("color", "red");
        }
        
    });	
	
    jQuery('#loginform').validate({
        rules: {
        	log: {
                required: true
            },
            pwd: {
                required: true
            }
        },
        messages: {
        	log: {
                required: "Please provide username."
            },
            pwd: {
                required: "Please provide password."
            }
        },
        errorPlacement: function (error, element) {
        	element.parent().after("<div class='form-error'></div>");
            error.appendTo(element.parent().next('.form-error'));
            element.parent().next().css("margin-bottom", "10px");
            element.parent().next().find('.error').css("color", "red");
        }
        
    });	
    
    jQuery('#loginform').on('submit', function(event){ 
    	event.preventDefault(event);
    	var form      = this;
		var username = jQuery("#user_login").val();
		var password = jQuery("#user_pass").val();
		if (jQuery('#loginform').valid()) {
		     check_exist_user(username,password,url, form);
		};
	});
});