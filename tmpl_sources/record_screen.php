<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <meta name="viewport" content="width=device-width" />
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
        -->
        <title>®Page_title®</title>
        <link rel="shortcut icon" type="image/ico" href="images/Generale/favicon.ico" />
        <link rel="apple-touch-icon" href="images/ipadIcon.png" /> 
        <link rel="stylesheet" type="text/css" href="css/style_recorder.css"/>
        <script type="text/javascript" src="js/AppearDissapear.js"></script>
        <script type="text/javascript" src="js/Selectbox-checkbox.js"></script>
        <script type="text/javascript" src="js/hover.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery-1.3.2.min.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery.scrollTo-min.js"></script>
        <script type="text/javascript" src="js/jQuery/jquery.serialScroll-min.js"></script>
        <script type="text/javascript" src="js/jQuery/function.js"></script>
        <script type="text/javascript" src="js/httpRequest.js"></script>
        <script src="js/jquery.colorbox.js"></script>
        <script type="text/javascript" src="js/loading_popup.js"></script>
        <script type="text/javascript">
            function offline_alert(){
                        window.alert("®offline_from_podc®");
            }
            function recording_start() {
                $.ajax({
                    type: 'GET',
                    url: "index.php?action=recording_start",
                    cache: false,
                    timeout: 4000, // 4 seconds
                    error: offline_alert,
                    success: function (html) {
                        if (html) {
                            // Everything went fine
                            document.getElementById('BoutonCancel').style.display = 'none';
                            MM_DisplayHideLayers('id1', '', 'hide', 'id2', '', 'show');
                            window.location = 'index.php';
                        }
                        else {
                            offline_alert();
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
                    timeout: 4000, // 4 seconds
                    error: offline_alert,
                    success: function (html) {
                        if (html) {
                            // Everything went fine
                            MM_DisplayHideLayers('id3', '', 'hide', 'id4', '', 'show');
                        }
                        else {
                            offline_alert();
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
                    timeout: 4000, // 4 seconds
                    error: offline_alert,
                    success: function (html) {
                        if (html) {
                            // Everything went fine
                            MM_DisplayHideLayers('id3', '', 'show', 'id4', '', 'hide');
                        }
                        else {
                            offline_alert()
                        }
                    }
                }
                );
            //    makeRequest('index.php', '?action=recording_resume', 'errorBox');
            }
            
            function recording_stop() {   
                var res = window.confirm('®Stop_recording®');
                if (!res)
                    return false;             
                $.ajax({
                    type: 'GET',
                    url: "index.php?action=view_record_submit", 
                    cache: false,
                    timeout: 4000, // 4 seconds
                    error: offline_alert,
                    success: function (html) {
                        if (html) {
                            // Everything went fine
                            $('html').html(html);
                        }
                        else {
                            offline_alert()
                        }
                    }
                }
                );
            //    makeRequest('index.php', '?action=recording_resume', 'errorBox');
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
                    <!-- Eventuel message d'erreur -->
                    <div id="errorBox" style="color: red; padding: 10px;"></div>
                    <!-- Message d'erreur FIN -->

                    <!-- Plan Quicktime video+Slides etc... -->
                    <div style="text-align: center; height: 180px;">
                        <?php if ($has_camera) {
                            ?>
                            <iframe src="index.php?action=view_screenshot_iframe&amp;source=cam" width="255px" height="178px" scrolling="false" frameborder="0"><img src="nopic.jpg" alt="®Iframes_unsupported®" /></iframe>
                            <?php
                        }
                        /* else {
                          ?>
                          <img src="images/disabled.jpg" alt="®Disabled®" style="display: block; position: absolute; top: 90px; left: 95px;" />
                          <?php
                          } */

                        if ($has_slides) {
                            ?>
                            <iframe src="index.php?action=view_screenshot_iframe&amp;source=slides" width="255px" height="178px" scrolling="false" style="overflow:hidden;" frameborder="0"q><img src="nopic.jpg" alt="®Iframes_unsupported®" /></iframe>
                            <?php
                        }
                        /* else {
                          ?>
                          <img src="images/disabled.jpg" alt="®Disabled®" style="display: block; position: absolute; top: 90px; left: 430px;" />
                          <?php
                          } */
                        ?>
                    </div>
                    <!-- <div id="camera">
                    </div> -->
                    <!-- Plan Quicktime video+Slides etc... [FIN] -->

                    <div id="boutonEnregistrement">
                        <!-- BOUTON ENREGISTREMENT PLAY / PAUSE / STOP -->
                        <div id="id1" <?php if ($redraw && $already_recording) echo 'style="display: none;"'; ?>>
                            <a href="javascript:recording_start();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image2', '', 'images/page2/BDemEnrg.png', 1)"><img src="images/page2/ADemEnrg.png" name="Image2" title="®Start_recording®" border="0" id="Image2" />®Start_recording®</a>
                        </div>
                        <!-- BOUTON ENREGISTREMENT PLAY / PAUSE / STOP [FIN] -->

                        <!-- BOUTON CAMERA + PLAN -->
                        <?php if ($cam_management_enabled && $_SESSION['recorder_type'] != 'slide') {
                            ?>
                            <div id='btnScenes' class="PlanCamera">
                                <a href="javascript:visibilite('divid5');" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image5', '', 'images/page2/BCamPlan.png', 0)"><img src="images/page2/ACamPlan.png" name="Image5" width="128" border="0" title="®Scenes®" id="Image5" />®Scenes®</a>
                            </div>
                            <?php
                        }
                        ?>


                        <div id="id2" <?php if (!$redraw || !$already_recording) echo 'style="display:none"'; ?>>
                            <!-- BOUTON STOP -->
                            <div id='btnStop' class="BtnStop">
                                <a href="javascript:recording_stop();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image3', '', 'images/page2/BStopEnr.png', 1)"><img src="images/page2/AStopEnr.png" name="Image3" title="®Stop_recording_hover®" border="0" id="Image3" />®Stop_recording_hover®</a>
                            </div>

                            <!-- Bouton pause -->
                            <div id="id3" <?php if ($redraw && $already_recording && $status == 'paused') echo 'style="display: none;"' ?>>
                                <span class="btnPause"><a href="javascript:recording_pause();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image4', '', 'images/page2/BPauseEnr.png', 1)"><img src="images/page2/APauseEnr.png" name="Image4" border="0" title="®Pause_recording®" id="Image4" />®Pause_recording®</a></span>
                            </div>

                            <!-- BOUTON resume -->
                            <div id="id4" <?php if (!$redraw || !$already_recording || $status == 'recording') echo 'style="display:none"'; ?>>
                                <span class="btnPause"><a href="javascript:recording_resume();" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image16', '', 'images/page2/BReprendreEnr.png', 1)"><img src="images/page2/AReprendreEnr.png" name="Image16" title="®Resume_recording®" border="0" id="Image16" />®Resume_recording®</a> </span>
                            </div>
                        </div>  
                    </div>
                    <!-- BOUTON CAMERA + PLAN [FIN] -->
                </div>

                <!-- Bloc cache avec choix des plans -->
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
                                    foreach ($positions as $position) {
                                        ?>
                                        <li><a href="javascript:move_camera('<?php echo $position; ?>');"><img src="<?php echo $cam_management_views_dir . $position . '.jpg'; ?>" name="<?php echo $position; ?>" width="235" height="157" border="0" title="<?php echo preg_replace('!_!', ' ', $position); ?>" id="<?php echo $position; ?>" /><br/><?php echo preg_replace('!_!', ' ', $position); ?></a></li>
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
                <!-- Bloc cache aevc choix des plans [Fin] -->

                <!-- BOUTON "RETOUR" -->
                <?php
                if (!$redraw || !$already_recording) {
                    ?>
                    <div id="BoutonCancel">
                        <a href="index.php?action=view_record_form&reset_player=true">®Back®</a>
                    </div>
                    <?php
                }
                ?>
                <!-- BOUTON RETOUR FIN -->
            </div>

            <!-- FOOTER - INFOS COPYRIGHT -->
            <?php include 'div_main_footer.php'; ?>
            <!-- FOOTER - INFOS COPYRIGHT [FIN] -->

        </div>
    </body>
</html>
