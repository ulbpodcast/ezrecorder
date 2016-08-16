
<!--
This div contains the form used to create a new record.
You should not have to use this template by itself. However, if you do, please
make sure $courselist is set and is an array of all courses available for the user
(assoc array: key = course name, value = course description)
-->
<script>
    $(document).ready(function() {
        $('#submit_button').click(function(e) {
            e.preventDefault();
            if (check_form()) {
                loading_popup();
                //submit immediately. The next page won't be loaded before recording is initialized so the popup will stay for the duration.
                setTimeout(function() {
                    document.forms['form1'].submit();
                }, 1);
            }
        });
    });
</script>
<form class="record_form" name="form1" id="form1" method="post" action="index.php" onsubmit="return false;">
    <div class="ensembleUn">

        <input type="hidden" name="action" value="submit_record_infos" />
        <!-- SELECT BOX - COURSE CHOICE -->
        <div class="cours">
            <label>®Course®:</label>
            <p>
                <?php if (!isset($courselist) || empty($courselist)) {
                    ?>
                    ®No_course_available®
                    <?php
                } else {
                    ?>
                    <select name="course" id="course">
                        <?php
                        foreach ($courselist as $course_name => $course_description) {
                            if ($course_name == $_SESSION['recorder_course']) {
                                ?>
                                <option selected="selected" value="<?php echo $course_name; ?>"><?php echo $course_name; ?> - <?php echo $course_description; ?></option>
                            <?php } else { ?>
                                <option value="<?php echo $course_name; ?>"><?php echo $course_name; ?> - <?php echo $course_description; ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                <?php }
                ?>
            </p>
        </div>
        <!-- SELECT BOX - COURSE CHOICE [END] -->

        <!-- TEXT FIELDS - TITLE INPUT -->
        <label>®Title®:</label>
        <input type="text" name="title" id="title" maxlength="70" value="<?php echo $_SESSION['title']; ?>"/>
        <!-- TEXT FIELDS - TITLE INPUT [END] -->

        <!-- TEXT FIELDS - DESCRIPTION -->
        <label>®Description®:</label>
        <textarea  name="description" rows="4" id="description"><?php echo $_SESSION['description']; ?></textarea>
        <!-- TEXT FIELDS - DESCRIPTION [END] -->
        <div class="spacer"></div>
    </div>

    <!-- CHECKBOX / RADIO BUTTON / CHOICE -->
    <p class="typechoice">®Record_type®:</p>
    <div id="WrapRadio">
        <?php if ($cam_enabled && $slide_enabled) { ?><div class="radioOne" onclick="set_record_type('camslide');" ><input id="radiocamslide" type="radio" name="record_type" value="camslide" class="styled" onclick="set_record_type('camslide');" /></div> <?php } ?>
        <?php if ($cam_enabled) { ?><div class="radioTwo" onclick="set_record_type('cam');" ><input id="radiocam" type="radio" name="record_type" value="cam" class="styled" onclick="set_record_type('cam');"/></div><?php } ?>
        <?php if ($slide_enabled) { ?><div class="radioThree" onclick="set_record_type('slide');" ><input id="radioslide" type="radio" name="record_type" value="slide" class="styled" onclick="set_record_type('slide');"/></div><?php } ?>
    </div>
    
    <?php
        //default record type if not any already defined
        if(!isset($_SESSION['recorder_type'])) {
            $default_type = "";
            if ($cam_enabled && $slide_enabled)
                $default_type = 'camslide';
            elseif ($cam_enabled)
                $default_type = 'cam';
            else if($slide_enabled)
                $default_type = 'slide';
            
            $_SESSION['recorder_type'] = $default_type;
        }
    ?>
    
    <script type="text/javascript"  language="JavaScript">
    //select the record type if it is already known
    $radioButton = document.getElementById("radio<?php echo $_SESSION['recorder_type']; ?>");
    if($radioButton)
        $radioButton.click();
    </script>
    <!-- CHECKBOX / RADIO BUTTON / CHOICE [END] -->

    <!-- CANCEL BUTTON / CONTINUE -->
    <div id="btn">   
        <label class="stream" <?php if (!$streaming_available) echo "style='visibility:hidden'"; ?>><input type="checkbox" name="streaming" value="enabled"> Streaming LIVE</label>
        <a class="deconnexion" href="index.php?action=logout" id="logout_button">®Deconnection®</a>
        <a class="continuer" id="submit_button" href="#">®Continue®</a></div>
</form>
<!-- CANCEL BUTTON / CONTINUE [END] -->