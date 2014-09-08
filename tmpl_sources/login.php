<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--
 * EZCAST EZrecorder
 *
 * Copyright (C) 2014 Université libre de Bruxelles
 *
 * Written by Michel Jansens <mjansens@ulb.ac.be>
 * 	      Arnaud Wijns <awijns@ulb.ac.be>
 *            Antoine Dewilde
 * UI Design by Julien Di Pietrantonio
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

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
                    <div style="padding-left: 30px; color: red; font-weight: bold;"><?php echo $error; ?></div>
                    <form id="login_form" method="post" action="index.php">
                        <input type="hidden" name="action" value="login" />
                        <div style="color: red; display: none;" id="IEalert">®Navigator_not_compatible®</div>
                        <div id="login_fields">
                            <label>®Login®:&nbsp;&nbsp;<input type="text" name="login" autocapitalize="off" autocorrect="off" tabindex="1" style="width: 150px;" /></label>
                            <br/>
                            <label>®Password®:&nbsp;&nbsp;<input type="password" name="passwd" autocapitalize="off" autocorrect="off" tabindex="2" /></label>
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