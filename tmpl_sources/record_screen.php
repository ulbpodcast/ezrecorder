<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!-- 
[autotest_record_screen] !! Please keep this this line for automated testing 
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
        <script type="text/javascript" src="js/AppearDissapear.js"></script>
        <script type="text/javascript" src="js/Selectbox-checkbox.js"></script>
        <script type="text/javascript" src="js/hover.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery-1.12.0.min.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery.scrollTo-min.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery.serialScroll-min.js"></script>
        <script type="text/javascript" src="js/jQuery/function.js"></script>
        <script type="text/javascript" src="js/httpRequest.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery.colorbox-min.js"></script>
        <script type="text/javascript" src="js/loading_popup.js"></script>
        <script type="text/javascript" src="js/footer.js"></script>
        <script type="text/javascript">
            function offline_alert() {
                window.alert("®offline_from_podc®");
            }
            function recording_start() {
                $.ajax({
                    type: 'GET',
                    url: "index.php?action=recording_start",
                    cache: false,
                    timeout: 10000,
                    error: offline_alert,
                    success: function (html) {
                        if (html) { // Everything went fine
                            document.getElementById('BoutonCancel').style.display = 'none';
                            MM_DisplayHideLayers('id1', '', 'hide', 'id2', '', 'show');
                            window.location = 'index.php';
                        }
                        else {
                            offline_alert();
                            location.reload();
                        }
                    }
                }
                );
                //      makeRequest('index.php', '?action=recording_start', 'errorBox');
            }

            function recording_pause() {
                $.ajax({
                    type: 'GET',
                    url: "index.php?action=recording_pause",
                    cache: false,
                    timeout: 10000,
                    error: offline_alert,
                    success: function (html) {
                        if (html) {  // Everything went fine
                            MM_DisplayHideLayers('id3', '', 'hide', 'id4', '', 'show');
                        }
                        else {
                            offline_alert();
                            location.reload();
                        }
                    }
                }
                );
                //    makeRequest('index.php', '?action=recording_pause', 'errorBox');
            }

            function recording_resume() {
                $.ajax({
                    type: 'GET',
                    url: "index.php?action=recording_resume",
                    cache: false,
                    timeout: 10000,
                    error: offline_alert,
                    success: function (html) {
                        if (html) { // Everything went fine
                            MM_DisplayHideLayers('id3', '', 'show', 'id4', '', 'hide');
                        }
                        else {
                            offline_alert()
                            location.reload();
                        }
                    }
                }
                );
                //    makeRequest('index.php', '?action=recording_resume', 'errorBox');
            }

            function recording_stop() {
                if(window.confirm('®Stop_recording®')) {
                    $.ajax({
                        type: 'GET',
                        url: "index.php?action=view_press_stop",
                        cache: false,
                        timeout: 15000,
                        error: offline_alert,
                        success: function (html) {
                            if (html) {  // Everything went fine
                                $('html').html(html);
                            }
                            else {
                                offline_alert()
                                location.reload();
                            }
                        }
                    }
                    );
                }
            }

            function move_camera(posname) {
                makeRequest('index.php', '?action=camera_move&position=' + posname, 'errorBox');
            }
        </script>
    </head>

    <body onload="MM_preloadImages('images/page3/BsupEnr.png', 'images/page3/BpubEnr.png', 'images/page3/BpubDEnr.png', 'images/page2/BDemEnrg.png', 'images/page2/BStopEnr.png', 'images/page2/BPauseEnr.png', 'images/page2/BReprendreEnr.png')">

        <div class="container">
            <?php include 'div_main_header.php'; ?>
            <div id="global2">
                <div id="global3">
                    <!-- ERROR MESSAGE -->
                    <div id="errorBox" style="color: red; padding: 10px;"></div>
                    <!-- ERROR MESSAGE FIN -->

                    <!-- Plan Quicktime video+Slides etc... -->
                    <div style="text-align: center; height: 180px;">
                        <?php if ($has_camera) {
                            ?>
                            <iframe id ="cam_frame" src="index.php?action=view_screenshot_iframe&amp;source=cam" width="255px" height="178px" scrolling="false" frameborder="0"><img src="nopic.jpg" alt="®Iframes_unsupported®" /></iframe>
                            <?php
                        }

                        if ($has_slides) {
                            ?>
                            <iframe id ="slide_frame" src="index.php?action=view_screenshot_iframe&amp;source=slides" width="255px" height="178px" scrolling="false" style="overflow:hidden;" frameborder="0"q><img src="nopic.jpg" alt="®Iframes_unsupported®" /></iframe>
                            <?php
                        }
                        ?>
                    </div>
                    <!-- <div id="camera">
                    </div> -->
                    <!-- Plan Quicktime video+Slides etc... [FIN] -->

                    <div id="boutonEnregistrement">
                        <!-- RECORD BUTTON PLAY / PAUSE / STOP -->
                        <div id="id1" <?php if ($redraw && $already_recording) echo 'style="display: none;"'; ?>>
                            <a href="javascript:recording_start();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image2', '', 'images/page2/BDemEnrg.png', 1)"><img src="images/page2/ADemEnrg.png" name="Image2" title="®Start_recording®" border="0" id="Image2" />®Start_recording®</a>
                        </div>
                        <!-- RECORD BUTTON PLAY / PAUSE / STOP [END] -->

                        <!-- CAMERA BUTTON + PLAN -->
                        <?php if ($cam_management_enabled && (!isset($_SESSION['recorder_type']) || $_SESSION['recorder_type'] != 'slide')) {
                            ?>
                            <div id='btnScenes' class="PlanCamera">
                                <a href="javascript:visibilite('divid5');" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image5', '', 'images/page2/BCamPlan.png', 0)"><img src="images/page2/ACamPlan.png" name="Image5" width="128" border="0" title="®Scenes®" id="Image5" />®Scenes®</a>
                            </div>
                            <?php
                        }
                        ?>

                        <div id="id2" <?php if (!$redraw || !$already_recording) echo 'style="display:none"'; ?>>
                            <!-- STOP BUTTON -->
                            <div id='btnStop' class="BtnStop">
                                <a href="javascript:recording_stop();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image3', '', 'images/page2/BStopEnr.png', 1)"><img src="images/page2/AStopEnr.png" name="Image3" title="®Stop_recording_hover®" border="0" id="Image3" />®Stop_recording_hover®</a>
                            </div>

                            <!-- PAUSE BUTTON -->
                            <div id="id3" <?php if ($redraw && $already_recording && $status == 'paused') echo 'style="display: none;"' ?>>
                                <a href="javascript:recording_pause();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image4', '', 'images/page2/BPauseEnr.png', 1)"><img src="images/page2/APauseEnr.png" name="Image4" border="0" title="®Pause_recording®" id="Image4" />®Pause_recording®</a>
                            </div>

                            <!-- RESUME BUTTON -->
                            <div id="id4" <?php if (!$redraw || !$already_recording || $status == 'recording') echo 'style="display:none"'; ?>>
                                <a href="javascript:recording_resume();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image16', '', 'images/page2/BReprendreEnr.png', 1)"><img src="images/page2/AReprendreEnr.png" name="Image16" title="®Resume_recording®" border="0" id="Image16" />®Resume_recording®</a>
                            </div>
                        </div>  
                        <!-- CAMERA BUTTON + PLAN [END] -->
                    </div>
                </div>

                <!-- Camera position bloc -->
                <?php if ($cam_management_enabled) {
                    ?>
                    <div id="divid5" style="display:none;">
                        <div id="buttons">
                            <a class="prev" href="#" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image11', '', 'images/page2/Barrow_02.png', 1)"><img src="images/page2/arrow_02.png" name="Image11"  title="Prev" alt="Prev" border="0" id="Image11" /></a>

                            <a class="next" href="#" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image12', '', 'images/page2/Barrow_01.png', 1)"><img src="images/page2/arrow_01.png" name="Image12"  border="0" alt="Next" title="Next" id="Image12" /></a>    
                            <br class="clear" />
                        </div>

                        <div id="galerie">
                            <div id="slideshow">
                                <ul>
                                    <?php
                                    if($positions)
                                    foreach ($positions as $position) {
                                        ?>
                                        <li><a href="javascript:move_camera('<?php echo $position; ?>');"><img src="<?php echo $cam_management_views_dir . $position . '.jpg?dummy=' . time(); ?>" name="<?php echo $position; ?>" width="235" height="157" border="0" title="<?php echo preg_replace('!_!', ' ', $position); ?>" id="<?php echo $position; ?>" /><br/><?php echo preg_replace('!_!', ' ', $position); ?></a></li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <!-- Camera position bloc [Fin] -->

                <!-- CANCEL BUTTON -->
                <?php
                if (!$redraw || !$already_recording) {
                    ?>
                    <div id="BoutonCancel">
                        <a href="index.php?action=view_record_form&reset_player=true">®Back®</a>
                    </div>
                    <?php
                }
                ?>
                <!-- CANCEL BUTTON FIN -->
            </div>

            <!-- FOOTER - INFOS COPYRIGHT -->
            <?php include 'div_main_footer.php'; ?>
            <!-- FOOTER - INFOS COPYRIGHT [FIN] -->

        </div>
    </body>
</html>
