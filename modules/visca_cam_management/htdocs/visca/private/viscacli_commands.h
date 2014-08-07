Commands without parameter:
===========================
set_zoom_tele            (set the zoom to maximum)
set_zoom_wide            (set the zoom to minimum)
set_zoom_stop            (stop zooming)
set_focus_far            (set the focus to far)
set_focus_near           (set the focus to near)
set_focus_stop           (stop focusing)
set_focus_one_push       (not for D30)
set_focus_infinity       (not for D30)
set_focus_autosense_high (not for D30)
set_focus_autosense_low  (not for D30)
set_whitebal_one_push    (push trigger for whitebalance in OnePush mode)
set_rgain_up             (not for D30)
set_rgain_down           (not for D30)
set_rgain_reset          (not for D30)
set_bgain_up             (not for D30)
set_bgain_down           (not for D30)
set_bgain_reset          (not for D30)
set_shutter_up           (increase the shutter speed, available only with
                          shutter_priority or AE_Manual)
set_shutter_down         (decrease the shutter speed, available only with
                          shutter_priority or AE_Manual)
set_shutter_reset        (reset the shutter speed, available only with
                          shutter_priority or AE_Manual)
set_iris_up              (open up the iris, available only with
                          iris_priority or AE_Manual)
set_iris_down            (close the iris, available only with
                          iris_priority or AE_Manual)
set_iris_reset           (reset the iris, available only with
                          iris_priority or AE_Manual)
set_gain_up              (increase the gain, available only with AE_Manual)
set_gain_down            (decrease the gain, available only with AE_Manual)
set_gain_reset           (reset the gain, available only with AE_Manual)
set_bright_up            (brighten the image, available only with bright_mode)
set_bright_down          (darken the image, available only with bright_mode)
set_bright_reset         (reset the image brightness,
                          available only with bright_mode)
set_aperture_up          (not for D30)
set_aperture_down        (not for D30)
set_aperture_reset       (not for D30)
set_exp_comp_up          (not for D30)
set_exp_comp_down        (not for D30)
set_exp_comp_reset       (not for D30)
set_title_clear          (not for D30)
set_irreceive_on         (enable IR remote controller)
set_irreceive_off        (disable IR remote controller)
set_irreceive_onoff      (toggle IR remote controller)
set_pantilt_home         (set pan/tilt position to center)
set_pantilt_reset        (initialize pan/tilt motors)
set_pantilt_limit_downleft_clear (remove pan/tilt limits
                                  for lower left position)
set_pantilt_limit_upright_clear (remove pan/tilt limits
                                 for upper right position)
set_datascreen_on         (show data display)
set_datascreen_off        (hide data display)
set_datascreen_onoff      (toggle data display)

Commands with one boolean parameter (0|1):
==========================================
set_power             (set standby power state)
set_keylock           (set or release keylock)
set_dzoom             (not for D30)
set_focus_auto        (turn autofocus on or off)
set_exp_comp_power    (not for D30)
set_slow_shutter_auto (not for D30)
set_backlight_comp    (turn backlight compensation on or off)
set_zero_lux_shot     (not for D30)
set_ir_led            (not for D30)
set_mirror            (not for D30)
set_freeze            (not for D30)
set_display           (not for D30)
set_date_display      (not for D30)
set_time_display      (not for D30)
set_title_display     (not for D30)

Commands with one integer parameter:
====================================
set_zoom_tele_speed <speed>      (set the zoom to maximum with a speed
                                  between 2 and 7)
set_zoom_wide_speed <speed>      (set the zoom to minimum with a speed
                                  between 2 and 7)
set_zoom_value <zoom>            (set the zoom to the given value
                                  between 0 and 1023)
set_focus_far_speed <speed>      (not for D30)
set_focus_near_speed <speed>     (not for D30)
set_focus_value <focus>          (set the focus to the given value
                                  between 1000 and 40959)
set_focus_near_limit <limit>     (not for D30)
set_whitebal_mode <mode>         (set the whitebalance mode to
                                  0: Auto, 1: Indoor, 2: Outdoor, 3: OnePush)
set_rgain_value <value>          (not for D30)
set_bgain_value <value>          (not for D30)
set_shutter_value <value>        (set the shutter value between
                                  0: 1/60 and 27: 1/10000)
set_iris_value <value>           (set the iris opening to a value
                                  between 0: closed and 17: F1.8)
set_gain_value <value>           (set the gain value between
                                  1: 0dB and 7: +18dB)
set_bright_value <value>         (not for D30)
set_aperture_value <value>       (not for D30)
set_exp_comp_value <value>       (not for D30)
set_auto_exp_mode <mode>         (set the AE mode to 0: Full Auto, 3: Manual,
                                  10: Shutter priority, 11: Iris priority,
				  13: Bright Mode)
set_wide_mode <mode>             (not for D30)
set_picture_effect <mode>        (not for D30)
set_digital_effect <mode>        (not for D30)
set_digital_effect_level <level> (not for D30)
memory_set <channel>             (save the current position to channel 0 to 5)
memory_recall <channel>          (recall the current position from
                                  channel 0 to 5)
memory_reset <channel>           (reset a channel 0 to 5)

Commands with two integer parameters:
=====================================
set_zoom_and_focus_value <zoom> <focus>             (not for D30,
                                                     zoom 0 to 1023,
						     focus 1000 to 40959)
set_pantilt_up <pan_speed> <tilt_speed>             (move up,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_down <pan_speed> <tilt_speed>           (move down,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_left <pan_speed> <tilt_speed>           (move left,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_right <pan_speed> <tilt_speed>          (move right,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_upleft <pan_speed> <tilt_speed>         (move up and left,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_upright <pan_speed> <tilt_speed>        (move up and right,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_downleft <pan_speed> <tilt_speed>       (move down and left,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_downright <pan_speed> <tilt_speed>      (move down and right,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_stop <pan_speed> <tilt_speed>           (stop moving,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20)
set_pantilt_limit_upright <pan_limit> <tilt_limit>  (limit movement
                                                     upper right corner,
						     pan limit: -879 to 880,
						     tilt limit: -299 to 300)
set_pantilt_limit_downleft <pan_limit> <tilt_limit> (limit movement
                                                     lower left corner,
						     pan limit: -879 to 880,
						     tilt limit: -299 to 300)

Commands with four integer parameters:
======================================
set_pantilt_absolute_position <pan_speed> <tilt_speed>
                              <pan_position> <tilt_position> (set position,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20,
						     pan_pos: -879 to 880,
						     tilt_pos: -299 to 300)
set_pantilt_relative_position <pan_speed> <tilt_speed>
                              <pan_position> <tilt_position> (set position,
                                                     pan_speed from 1 to 24,
						     tilt speed from 1 to 20,
						     pan_pos: -879 to 880,
						     tilt_pos: -299 to 300)
set_title_params <vposition> <hposition> <color> <blink> (not for D30,
                                                          set title params)

Commands with five parameters:
==============================
set_date_time <year> <month> <day> <hour> <minute>       (not for D30,
                                                          set date and time)
set_title <vposition> <hposition> <color> <blink> <text> (not for D30,
                                                          set title params
                                                          and title text)

Commands that return one boolean value:
=======================================
get_power          (returns 1 if in standby mode, 0 otherwise)
get_dzoom          (not for D30)
get_focus_auto     (returns 1 if autofocus, 0 otherwise)
get_exp_comp_power (not for D30)
get_backlight_comp (returns 1 if backlight compensation is on, 0 otherwise)
get_zero_lux_shot  (not for D30)
get_ir_led         (not for D30)
get_mirror         (not for D30)
get_freeze         (not for D30)
get_display        (not for D30)
get_datascreen     (returns 1 if a datascreen is displayed, 0 otherwise)

Commands that return one integer value:
=======================================
get_zoom_value           (returns a value between 0 and 1023)
get_focus_value          (returns a value between 1000 and 40959)
get_focus_auto_sense     (not for D30)
get_focus_near_limit     (not for D30)
get_whitebal_mode        (returns the whitebalance mode:
                          0: Auto, 1: Indoor, 2: Outdoor, 3: OnePush)
get_rgain_value          (not for D30)
get_bgain_value          (not for D30)
get_auto_exp_mode        (returns the auto exposure mode:
                           0: Full Auto, 3: Manual,
                          10: Shutter priority, 11: Iris priority,
                          13: Bright Mode)
get_slow_shutter_auto    (not for D30)
get_shutter_value        (returns the shutter value between
                          0: 1/60 and 27: 1/10000)
get_iris_value           (returns the iris value between
                          0: closed and 17: F1.8
get_gain_value           (returns the gain value between 1: 0dB and 7: +18dB)
get_bright_value         (not for D30)
get_exp_comp_value       (not for D30)
get_aperture_value       (not for D30)
get_wide_mode            (not for D30)
get_picture_effect       (not for D30)
get_digital_effect       (not for D30)
get_digital_effect_level (not for D30)
get_memory               (returns the current memory preset position 0 to 5)
get_id                   (returns the camera id)
get_videosystem          (returns the video sytstem: 0 for NTSC, 1 for PAL)
get_pantilt_mode         (returns the pantilt status, what is this?)

Commands that return two integer values:
========================================
get_pantilt_maxspeed (returns max_pan_speed and max_tilt_speed)
get_pantilt_position (returns pan_position: -860..862
                      and tilt_position: -281..283)

==================================================
Commands not yet in libVISCA:
==================================================

Commands without parameter:
===========================
set_at_mode_onoff           (Target tracking mode On/Off)
set_at_ae_onoff             (Auto exposure for target tracking mode On/Off)
set_at_autozoom_onoff       (Auto zoom for target tracking mode On/Off)
set_atmd_framedisplay_onoff (Frame display for target tracking or
                             motion detection mode On/Off)
set_at_frameoffset_onoff    (Frame offset control for
                             target tracking mode On/Off)
set_atmd_startstop          (Start or stop target tracking or
                             motion detection)
set_at_chase_next           (Select target tracking chase mode 1/2/3)
set_md_mode_onoff           (Motion detection mode On/Off)
set_md_frame                (Set detection area/size for motion detection)
set_md_detect               (Select detecting frame for motion detection,
                             1, 2 or 1+2)
set_at_lostinfo             (returns when target is lost
                             in target tracking mode)
set_md_lostinfo             (returns when motion is detected within frame
                             in motion detection mode)
set_md_measure_mode1_onoff  (Set motion detection measure mode 1 On/Off)
set_md_measure_mode2_onoff  (Set motion detection measure mode 2 On/Off)

Commands with one boolean parameter (0|1):
==========================================
set_at_mode           (Target tracking mode On/Off)
set_at_ae             (Auto exposure for target tracking mode On/Off)
set_at_autozoom       (Auto zoom for target tracking mode On/Off)
set_atmd_framedisplay (Frame display for target tracking or
                       motion detection mode On/Off)
set_at_frameoffset    (Frame offset control for target tracking mode On/Off)
set_md_mode           (Motion detection mode On/Off)
set_md_measure_mode1  (Set motion detection measure mode 1 On/Off)
set_md_measure_mode2  (Set motion detection measure mode 2 On/Off)

Commands with one integer parameter:
====================================
set_wide_con_lens <conversion> (AT compensation when a wide conversion lens
                                is installed,
				0: No conversion to 7: X0.6 conversion)
set_at_chase <chase_mode>      (Select target tracking chase mode 0, 1 or 2)
set_at_entry <entry>           (Select target study mode for target tracking
                                available modes: 0 to 3)
set_md_adjust_ylevel <level>   (adjust level of detection from 0 to 15)
set_md_adjust_huelevel <level> (adjust level of detection from 0 to 15)
set_md_adjust_size <level>     (adjust level of detection from 0 to 15)
set_md_adjust_disptime <level> (adjust level of detection from 0 to 15)
set_md_adjust_refmode <mode>   (set refreshmode from 0 to 2)
set_md_adjust_reftime <time>   (set refreshtime from 0 to 15)

Commands that return one boolean value:
=======================================
get_keylock (returns 1 if keylock is set, 0 otherwise)

Commands that return one integer value:
=======================================
get_wide_con_lens     (returns which wide conversion lens is installed,
                       0: No conversion to 7: X0.6 conversion)
get_atmd_mode         (returns the current AT or MD mode:
                       0: Normal mode, 1: AT mode, 2: MD mode)
get_at_mode           (returns the AT status,
                       see D30/31 Command List for details
get_at_entry          (returns the AT entry: 0 to 3)
get_md_mode           (returns the MD status,
                       see D30/31 Command List for details)
get_md_ylevel         (returns level of detection from 0 to 15)
get_md_huelevel       (returns level of detection from 0 to 15)
get_md_size           (returns level of detection from 0 to 15)
get_md_disptime       (returns level of detection from 0 to 15)
get_md_refmode        (returns refreshmode from 0 to 2)
get_md_reftime        (returns refreshtime from 0 to 15)

Commands that return three integer values:
==========================================
get_at_obj_pos (returns the center position of the detection frame divided
                by 48x30 pixels and a status: 0=Setting, 1=Tracking, 2=Lost)
get_md_obj_pos (returns the center position of the detection frame divided
                by 48x30 pixels and a status: 1=UnDetect, 2=Detected)
*/
