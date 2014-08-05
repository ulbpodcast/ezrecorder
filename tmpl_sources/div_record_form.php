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

<!--
This div contains the form used to create a new record.
You should not have to use this template by itself. However, if you do, please
make sure $courselist is set and is an array of all courses available for the user
(assoc array: key = course name, value = course description)
-->
    <form class="record_form" name="form1" method="post" action="index.php" onsubmit="return false;">
<div class="ensembleUn">

    <input type="hidden" name="action" value="submit_record_infos" />
<!-- SELECT BOX - CHOIX DES COURS -->
<div class="cours">
<label>®Course®:</label>
<p>
    <?php if(!isset($courselist) || empty($courselist)) {
        ?>
        ®No_course_available®
        <?php
    } else {
        ?>
        <select name="course" id="course">
            <?php foreach($courselist as $course_name => $course_description) {
                if ($course_name == $_SESSION['recorder_course']) { ?>
                    <option selected="selected" value="<?php echo $course_name; ?>"><?php echo $course_name; ?> - <?php echo $course_description; ?></option>
                <?php } else { ?>
                    <option value="<?php echo $course_name; ?>"><?php echo $course_name; ?> - <?php echo $course_description; ?></option>
                <?php }
            } ?>
        </select>
        <?php
    } ?>
</p>
</div>
<!-- SELECT BOX - CHOIX DES COURS [FIN] -->

<!-- CHAMPS TEXTE - CHOIX TITRE -->
<label>®Title®:</label>
<input type="text" name="title" id="title" maxlength="70" value="<?php echo $_SESSION['title']; ?>"/>
<!-- CHAMPS TEXTE - CHOIX TITRE [FIN] -->

<!-- CHAMPS TEXTE - DESCRIPTION DU PODCAST -->
<label>®Description®:</label>
<textarea  name="description" rows="4" id="description"><?php echo $_SESSION['description']; ?></textarea>
<!-- CHAMPS TEXTE - DESCRIPTION DU PODCAST [FIN] -->
<div class="spacer"></div>
</div>

<!-- CHECKBOX / BOUTON RADIO / CHOIX -->
<p class="typechoice">®Record_type®:</p>
<div id="WrapRadio">
<?php if ($cam_enabled && $slide_enabled){ ?><div class="radioOne" onclick="set_record_type('camslide');" ><input id="radiocamslide" type="radio" name="record_type" value="camslide" class="styled" onclick="set_record_type('camslide');" /></div> <?php } ?>
<?php if ($cam_enabled){ ?><div class="radioTwo" onclick="set_record_type('cam');" ><input id="radiocam" type="radio" name="record_type" value="cam" class="styled" onclick="set_record_type('cam');"/></div><?php } ?>
<?php if ($slide_enabled){ ?><div class="radioThree" onclick="set_record_type('slide');" ><input id="radioslide" type="radio" name="record_type" value="slide" class="styled" onclick="set_record_type('slide');"/></div><?php } ?>
</div>
<script type="text/javascript"  language="JavaScript">
    //select the record type if it is already known
    document.getElementById("radio<?php echo $_SESSION['recorder_type'];?>").click();
</script>
<!-- CHECKBOX / BOUTON RADIO / CHOIX [FIN] -->


<!-- BOUTON ANNULER / CONTINUER -->
<div id="btn">
<a class="deconnexion" href="index.php?action=logout" id="logout_button">®Deconnection®</a>
<a class="continuer" id="submit_button" onclick="if(check_form()) loading_popup(); else return false;" href="javascript:document.forms['form1'].submit();">®Continue®</a></div>
   </form>
<!-- BOUTON ANNULER / CONTINUER [FIN] -->