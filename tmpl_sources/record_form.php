<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <meta name="viewport" content="width=device-width" />
        <?php /*
          This is the template of the new record form. This template is displayed when a new user logs in.
         */ ?>
        <title>®Page_title®</title>
        <link rel="shortcut icon" type="image/ico" href="images/Generale/favicon.ico" />
        <link rel="apple-touch-icon" href="images/ipadIcon.png" /> 
        <link rel="stylesheet" type="text/css" href="css/style_recorder.css"/>
        <link rel="stylesheet" type="text/css" href="css/colorbox.css"/>
        <link rel="stylesheet" href="css/font-awesome.min.css"/>
        <script type="text/javascript" src="js/Selectbox-checkbox.js"></script>
        <script type="text/javascript" src="js/hover.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery-1.12.0.min.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery.colorbox-min.js"></script>
        <script type="text/javascript">
            var record_type = '';

            /**
             * Submits the form whenever someone clicks on "Continue"
             */
            function check_form() {
                var album = document.getElementById('course').value;
                var title = document.getElementById('title').value;
                var description = document.getElementById('description').value;
                if (title == '') {
                    window.alert('®No_title_provided®');
                    return false;
                }
                else if (title.length > <?php
                    require_once 'global_config.inc';
                    global $title_max_length;
                    echo $title_max_length;
            ?>                          ) {
                    window.alert('®Title_too_long®');
                    return false;
                }
                else if (record_type == '') {
                    window.alert('®No_record_type_chosen®');
                    return false;
                }

                return true;
            }

            function set_record_type(value) {
                record_type = value;
            }

            function loading_popup() {
                //            $.colorbox({inline: true, href: '#loading_popup', overlayClose: false});
                $("#loading").show();
            }
        </script>
    </head>

    <body>
        <div class="container">
            <?php include 'div_main_header.php'; ?>
            <div id="global">
                <?php
                global $notice;
                if (isset($notice)) {
                    require 'div_error_message.php';
                }
                ?>
                <?php include_once 'div_record_form.php'; ?>
            </div>

            <?php include 'div_main_footer.php'; ?>

        </div>

        <div id="loading">
            <div class="popup" id="loading_popup" style="background-color: black">
                <!--div style="text-align: center;"><img src="images/loading.gif" /></div>
                <br/><br/-->
                <div style="color:white; text-align: center; border-style: solid; border-width: 1px; border-color: white; padding: 5px; font-family: Arial; font-size: 0.85em">®wait®</div>
            </div>
        </div>
    </body>
</html>