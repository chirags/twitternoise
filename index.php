<?php
require_once('twitterOAuth.php');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Attacking Twitter Noise</title>
<style type="text/css"> body { margin:0; padding:0; } </style>
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/fonts/fonts-min.css" />
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/button/assets/skins/sam/button.css" />
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/container/assets/skins/sam/container.css" />
<script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/connection/connection-min.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/element/element-min.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/button/button-min.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/dragdrop/dragdrop-min.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/container/container-min.js"></script>
</head>

<body class=" yui-skin-sam">
<center><h1>Attacking Twitter Noise</h1>
<p>Inspired by ROBOT9000 and #<a href="http://blag.xkcd.com/2008/01/14/robot9000-and-xkcd-signal-attacking-noise-in-chat/">xkcd-signal</a>
</p></center>

<style>
label { display:block;float:left;width:45%;clear:left; }
.clear { clear:both; }
#resp { margin:10px;padding:5px;background:#fff;}
#resp li { font-family:monospace }
textarea {
-x-system-font:none;
font-family:'Lucida Grande',sans-serif;
font-size:1.15em;
font-size-adjust:none;
font-stretch:normal;
font-style:normal;
font-variant:normal;
font-weight:normal;
height:2.6em;
line-height:1.1;
overflow:auto;
padding:5px;
width:520px;
}
</style>

<script>
YAHOO.namespace("twitnoise");

function init() {
    var handleSubmit = function() {
        this.submit();
    };
    var handleSuccess = function(o) {
        var response = o.responseText;
        response = response.split("<!")[0];
        document.getElementById("resp").innerHTML = response;
    };
    var handleFailure = function(o) {
        alert("Submission failed: " + o.status);
    };

    // Instantiate the Dialog
    YAHOO.twitnoise.dialog1 = new YAHOO.widget.Dialog("dialog1", 
                            { width : "43em",
                              close : false,
                              fixedcenter : true,
                              visible : true, 
                              constraintoviewport : true,
                              hideaftersubmit : false,
                              buttons : [ { text:"Update", handler:handleSubmit, isDefault:true } ]
                            });

    YAHOO.twitnoise.dialog1.validate = function() {
        var data = this.getData();
        if (data.textarea == "") {
            document.getElementById("resp").innerHTML= "You must enter a status";
            return false;
        } else {
            return true;
        }
    };
    YAHOO.twitnoise.dialog1.callback = { success: handleSuccess, failure: handleFailure };
    YAHOO.twitnoise.dialog1.render();
}

YAHOO.util.Event.onDOMReady(init);
</script>

<div id="dialog1">
<div class="hd">What are you doing?</div>
<div class="bd">
<form method="GET" action="handleStatusUpdate.php"><textarea rows="2" cols="50" name="textarea"></textarea></form>
<div id="resp"></div> 

</div></div>
</body>
</html>
