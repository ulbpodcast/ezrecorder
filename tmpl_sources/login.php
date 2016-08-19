<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- Please keep this at second line for automated testing: autotest_login_screen
This is the template for the login screen.
-->
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <meta name="viewport" content="width=device-width" />
        <title>®Page_title®</title>
        <link rel="shortcut icon" type="image/ico" href="images/Generale/favicon.ico" />
        <link rel="apple-touch-icon" href="images/ipadIcon.png" /> 
        <link rel="stylesheet" type="text/css" href="css/style_recorder.css"/>
        <script type="text/javascript" src="js/Selectbox-checkbox.js"></script>
        <script type="text/javascript" src="js/hover.js"></script>
        <style type="text/css">
            body {
                color: #000000;
                font-size: 10px;
            }
        </style>

        <script type="text/javascript">
            function iecheck() {
                if (navigator.appVersion.indexOf("MSIE 6.") != -1 || navigator.appVersion.indexOf("MSIE 7.") != -1) {
                    document.getElementById('IEalert').style.display = '';
                }
            }
        </script>
    </head>

    <body onload="iecheck();">
        <div class="container">
            <?php include 'div_main_header.php'; ?>
            <div id="global">
                <p>
                    <div style="padding-left: 30px; color: red; font-weight: bold;"></div>
                    <form id="login_form" method="post" action="index.php">
                        <input type="hidden" name="action" value="login" />
                        <div style="color: red; display: none;" id="IEalert">®Navigator_not_compatible®</div>
                        <div id="login_fields">
                            <label>®Login®:&nbsp;&nbsp;</label><input type="text" name="login" autocapitalize="off" autocorrect="off" tabindex="1" style="width: 150px;" />
                            <br/>
                            <label>®Password®:&nbsp;&nbsp;</label><input type="password" name="passwd" autocapitalize="off" autocorrect="off" tabindex="2" style="width: 150px;"/>
                        </div>

                        <select name="lang" style="width: 100px;" tabindex="3">
                            <option value="en">English</option>
                            <option value="fr" selected="selected">Français</option>
                        </select>

                        <input type="submit" style="width: 100px;" tabindex="4" name="®Enter®" value="®Enter®" />

                    </form>
                </p>

            </div>

            <?php include 'div_main_footer.php'; ?>

        </div>

    </body>
</html>