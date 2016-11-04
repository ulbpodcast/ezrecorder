<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!-- 
[autotest_record_submit] !! Please keep this this line for automated testing 
-->

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <meta name="viewport" content="width=device-width" />
        <?php
        /*
          Last step in the recording: we ask the user if they want their record to be submitted.
         */
        ?>
        <title>®Page_title®</title>
        <link rel="shortcut icon" type="image/ico" href="images/Generale/favicon.ico" />
        <link rel="apple-touch-icon" href="images/ipadIcon.png" /> 
        <link rel="stylesheet" type="text/css" href="css/Style_recorder.css"/>
        <script type="text/javascript" src="js/hover.js"></script>
        <script type="text/javascript" src="js/httpRequest.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery-1.12.0.min.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery.colorbox-min.js"></script>
        <script type="text/javascript" src="js/footer.js"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                $.colorbox.remove();
            });

            function stop_and_publish(moderation) {
                var message = '';
                if (moderation == 'true') {
                    message = "®Unpublish®";
                }
                else {
                    message = "®Publish®";
                }

                var res = window.confirm(message);
                if (res)
                    makeRequest('index.php', '?action=stop_and_publish&moderation=' + moderation, 'global');
            }

            function cancel() {
                var message = "®Cancel®";

                var res = window.confirm(message);
                if (res) {
                    makeRequest('index.php', '?action=recording_cancel', 'global');
                }
            }

           function loading_popup() {
                //$.colorbox({inline: true, href: '#loading_popup', overlayClose: false});
            }
        </script>
    </head>

    <body onload="MM_preloadImages('images/page3/BsupEnr.png', 'images/page3/BpubEnr.png', 'images/page3/BpubDEnr.png', 'images/loading.gif')">
        <div class="container">
            <?php include 'div_main_header.php'; ?>
            <div id="global">

                <!-- Eventuel message d'erreur -->
                <div id="errorBox" style="color: red; padding: 10px;"></div>
                <!-- Message d'erreur FIN -->

                <!-- TROIS BOUTONS  SUPPRIMER / PUBLIER PLUS TARD / PUBLIER DIRECTEMENT -->
                <div id="troisbouton">
                    <a id="btnCancel" href="javascript:cancel();" onclick="loading_popup();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image2', '', 'images/page3/BsupEnr.png', 1)"><img src="images/page3/AsupEnr.png" name="Image2" title="®Delete_record®" width="128" border="0" id="Image2" />®Delete_record®</a>
                    <a id="btnPriv" href="javascript:stop_and_publish('true');" onclick="loading_popup();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image3', '', 'images/page3/BpubEnr.png', 1)"><img src="images/page3/ApubEnr.png" name="Image3" title="®Publish_in_private_album®" width="128" border="0" id="Image3" />®Publish_in_private_album®</a> 
                    <a id="btnPub" href="javascript:stop_and_publish('false');" onclick="loading_popup();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image4', '', 'images/page3/BpubDEnr.png', 1)"><img src="images/page3/ApubDEnr.png" name="Image4" title="®Publish_in_public_album®" width="128" border="0" id="Image4" />®Publish_in_public_album®</a>
                </div>
            </div>  
            <!-- TROIS BOUTONS  SUPPRIMER / PUBLIER PLUS TARD / PUBLIER DIRECTEMENT [FIN] -->

            <!-- FOOTER - INFOS COPYRIGHT -->
            <?php include 'div_main_footer.php'; ?>

            <!-- FOOTER - INFOS COPYRIGHT [FIN] -->
        </div>

        <div style="display: none;">
            <div class="popup" id="loading_popup">
                <img src="images/loading.gif" />
            </div>
        </div>
    </body>
</html>
