<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!-- 
[autotest_record_screen] !! Please keep this line for automated testing 
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
    </head>

    <body onload="MM_preloadImages('images/page3/BsupEnr.png', 'images/page3/BpubEnr.png', 'images/page3/BpubDEnr.png', 'images/page2/BDemEnrg.png', 'images/page2/BStopEnr.png', 'images/page2/BPauseEnr.png', 'images/page2/BReprendreEnr.png')">

        <div class="container">
            <?php include 'div_main_header.php'; ?>
            <div id="global2">
                <div id="global3">
                    <div id="errorBox" style="color: red; padding: 10px;"></div>

                    <!-- Display div -->
                    <div style="text-align: center; height: 180px;">
                        <?php if ($has_camera) {
                            ?>
                            <!-- generate blank img -->
                            <img id="cam_frame" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" height="157px" width="255px" border="0" alt="cam preview"/>
                        <?php
                        }

                        if ($has_slides) {
                        ?>
                            <!-- generate blank img -->
                            <img id="slides_frame" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" height="157px" width="255px" border="0" alt="slide preview"/>
                        <?php
                        }
                        ?>
                        <!-- Stay hidden until first successful update -->
                        <canvas hidden style="position: relative; border: black 1px solid" id="meter" width="15" height="156"></canvas>
                    </div>
                
                    <!-- Controls div -->
                    <div id="boutonEnregistrement">
                        <div id="id1" <?php if ($redraw && $already_recording) echo 'style="display: none;"'; ?>>
                            <a href="javascript:recording_start();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image2', '', 'images/page2/BDemEnrg.png', 1)"><img src="images/page2/ADemEnrg.png" name="Image2" title="®Start_recording®" border="0" id="Image2" />®Start_recording®</a>
                        </div>

                        <?php if ($cam_management_enabled && $record_type != 'slide') {
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

                <!-- Camera position bloc (hidden at load) -->
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
            </div>

            <?php include 'div_main_footer.php'; ?>
        </div>
        
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

         function update_sound_status() {
             $.ajax({
                 type: 'GET',
                 url: "index.php?action=view_sound_status",
                 cache: false,
                 timeout: 5000,
                 success: function (db) {
                     if (db) { // Everything went fine
                        $("#meter").show();
                        set_vu_level(db);
                     }
                 }
             }
             );
         }

         function move_camera(posname) {
             makeRequest('index.php', '?action=camera_move&position=' + posname, 'errorBox');
         }

         function init_vu_meter() {
              var canvas = document.querySelector('#meter');
             var ctx = canvas.getContext('2d');
             var w = canvas.width;
             var h = canvas.height;

             //fill the canvas first
             ctx.fillStyle = '#555';
             ctx.fillRect(0,0,w,h);
         }

         function set_vu_level(db) {
             var canvas = document.querySelector('#meter');
             var ctx = canvas.getContext('2d');
             var w = canvas.width;
             var h = canvas.height;

             var grad = ctx.createLinearGradient(w/10,h*0.2,w/10,h*0.95);
             grad.addColorStop(0,'#990000'); //red
             grad.addColorStop(-6/-72,'#ffcc00'); //yellow
             grad.addColorStop(1,'#009900'); //green
             //fill the background
             ctx.fillStyle = '#555';
             ctx.fillRect(0,0,w,h);
             ctx.fillStyle = grad;
             //draw the rectangle
             ctx.fillRect(w/10,h*0.8*(db/-72),w*8/10,(h*0.99)-h*0.8*(db/-72));
         }

         function refresh_all_previews() {
            <?php if ($has_camera) { ?>
              refresh_preview("cam");
            <?php } ?>
            <?php if ($has_slides) { ?>
              refresh_preview("slides");
            <?php } ?>
         }
         function refresh_preview(source_type) {
             var img_element = document.getElementById(source_type + '_frame');
             img_element.src = 'index.php?action=view_screenshot_image&source=' + source_type + '&rand=' + Math.random();
         }



        init_vu_meter();
        
        <?php 
        if(sound_info_available()) { ?>
        setInterval(function() {
            update_sound_status();
        }, 1500);
        <?php } ?>

        
        setInterval(function() {
            refresh_all_previews();
        }, 2000);
 
        //also fetch first images immediately
        refresh_all_previews();
     </script>
    </body>
</html>
