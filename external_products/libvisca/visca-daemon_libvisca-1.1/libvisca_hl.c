/*
 * VISCA(tm) Camera Control Library - Highlevel Functionality
 * Copyright (C) 2004 Simon Bichler
 *
 * Written by Simon Bichler <bichlesi@in.tum.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>
#include <sys/ioctl.h>
#include <errno.h>
#include <termios.h>

#include "libvisca_hl.h"

void VISCA_open_interface(VISCAInterface_t *interface, VISCACamera_t *camera, char *ttydev) {
  int camera_num;
  if (VISCA_open_serial(interface, ttydev)!=VISCA_SUCCESS) {
    fprintf(stderr,"unable to open serial device %s\n",ttydev);
    exit(1);
  }

  (*interface).broadcast=0;
  VISCA_set_address(interface, &camera_num);
  (*camera).address=1;
  VISCA_clear(interface, camera);
  VISCA_get_camera_info(interface, camera);
#if DEBUG 
  fprintf(stderr,"Camera initialisation successful.\n");
#endif
}

void VISCA_close_interface(VISCAInterface_t *interface) {
  unsigned char packet[3000];
  int i, bytes;

  // read the rest of the data: (should be empty)
  ioctl((*interface).port_fd, FIONREAD, &bytes);
  if (bytes>0) {
    fprintf(stderr, "ERROR: %d bytes not processed: ", bytes);
    read((*interface).port_fd, &packet, bytes);
    for (i=0;i<bytes;i++) {
      fprintf(stderr,"%2x ",packet[i]);
    }
    fprintf(stderr,"\n");
  }
  VISCA_close_serial(interface);
}

/* This subroutine tries to execute the commandline given in char *commandline
 * 
 * One of the following codes is returned:
 *
 * Success:
 * 10: command successfully executed
 * 11: command successfully executed, return value in ret1
 * 12: command successfully executed, return values in ret1 and ret2
 * 13: command successfully executed, return values in ret1, ret2 and ret3
 *
 * Error:
 * 40: command unknow
 * 41: missing or unknown arg1
 * 42: missing or unknown arg2
 * 43: missing or unknown arg3
 * 44: missing or unknown arg4
 * 45: missing or unknown arg5
 * 46: camera returned an error  
 */
int VISCA_doCommand(char *commandline, int *ret1, int *ret2, int *ret3,
    VISCAInterface_t *interface, VISCACamera_t *camera) {
  /*Variables for the user specified command and arguments*/
  char *command;
  char *arg1;
  char *arg2;
  char *arg3;
  char *arg4;
  char *arg5;
  int intarg1;
  int intarg2;
  int intarg3;
  int intarg4;
  int intarg5;
  int boolarg;
  VISCATitleData_t *temptitle;
  
  /*Variables that hold return values from VISCA routines*/
  UInt8_t value8, value8b, value8c;
  UInt16_t value16;
  
  /*tokenize the commandline*/
  command = strtok(commandline, " ");
  arg1 = strtok(NULL, " ");
  arg2 = strtok(NULL, " ");
  arg3 = strtok(NULL, " ");
  arg4 = strtok(NULL, " ");
  arg5 = strtok(NULL, " ");
  
  /*Try to convert the arguments to integers*/
  if (arg1 != NULL) {
    intarg1 = atoi(arg1);
  }
  if (arg2 != NULL) {
    intarg2 = atoi(arg2);
  }
  if (arg3 != NULL) {
    intarg3 = atoi(arg3);
  }
  if (arg4 != NULL) {
    intarg4 = atoi(arg4);
  }
  if (arg5 != NULL) {
    intarg5 = atoi(arg5);
  }
  
  /*Try to find a boolean value*/
  if ((arg1 != NULL) && (strcmp(arg1, "true") == 0)) {
    boolarg = 2;
  } else if ((arg1 != NULL) && (strcmp(arg1, "false") == 0)) {
    boolarg = 3;
  } else if ((arg1 != NULL) && (strcmp(arg1, "1") == 0)) {
    boolarg = 2;
  } else if ((arg1 != NULL) && (strcmp(arg1, "0") == 0)) {
    boolarg = 3;
  } else {
    boolarg = -1;
  }
  
#if DEBUG
  fprintf(stderr, "command: %s\n", command);
  fprintf(stderr, "arg1: %s\narg2: %s\narg3: %s\narg4: %s\narg5: %s\n", 
          arg1, arg2, arg3, arg4, arg5);
  fprintf(stderr, 
          "intarg1: %i\nintarg2: %i\nintarg3: %i\nintarg4:%i\nintarg5:%i\n", 
          intarg1, intarg2, intarg3, intarg4, intarg5);
  fprintf(stderr, "boolarg: %i\n", boolarg);
#endif

  if (strcmp(command, "set_zoom_tele") == 0) {
    if (VISCA_set_zoom_tele(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_zoom_wide") == 0) {
    if (VISCA_set_zoom_wide(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_zoom_stop") == 0) {
    if (VISCA_set_zoom_stop(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_focus_far") == 0) {
    if (VISCA_set_focus_far(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_focus_near") == 0) {
    if (VISCA_set_focus_near(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_focus_stop") == 0) {
    if (VISCA_set_focus_stop(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_focus_one_push") == 0) {
    if (VISCA_set_focus_one_push(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_focus_infinity") == 0) {
    if (VISCA_set_focus_infinity(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_focus_autosense_high") == 0) {
    if (VISCA_set_focus_autosense_high(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_focus_autosense_low") == 0) {
    if (VISCA_set_focus_autosense_low(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_whitebal_one_push") == 0) {
    if (VISCA_set_whitebal_one_push(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_rgain_up") == 0) {
    if (VISCA_set_rgain_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_rgain_down") == 0) {
    if (VISCA_set_rgain_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_rgain_reset") == 0) {
    if (VISCA_set_rgain_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_bgain_up") == 0) {
    if (VISCA_set_bgain_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_bgain_down") == 0) {
    if (VISCA_set_bgain_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_bgain_reset") == 0) {
    if (VISCA_set_bgain_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_shutter_up") == 0) {
    if (VISCA_set_shutter_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_shutter_down") == 0) {
    if (VISCA_set_shutter_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_shutter_reset") == 0) {
    if (VISCA_set_shutter_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_iris_up") == 0) {
    if (VISCA_set_iris_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_iris_down") == 0) {
    if (VISCA_set_iris_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_iris_reset") == 0) {
    if (VISCA_set_iris_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_gain_up") == 0) {
    if (VISCA_set_gain_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_gain_down") == 0) {
    if (VISCA_set_gain_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_gain_reset") == 0) {
    if (VISCA_set_gain_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_bright_up") == 0) {
    if (VISCA_set_bright_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_bright_down") == 0) {
    if (VISCA_set_bright_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_bright_reset") == 0) {
    if (VISCA_set_bright_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_aperture_up") == 0) {
    if (VISCA_set_aperture_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_aperture_down") == 0) {
    if (VISCA_set_aperture_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_aperture_reset") == 0) {
    if (VISCA_set_aperture_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_exp_comp_up") == 0) {
    if (VISCA_set_exp_comp_up(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_exp_comp_down") == 0) {
    if (VISCA_set_exp_comp_down(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_exp_comp_reset") == 0) {
    if (VISCA_set_exp_comp_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_title_clear") == 0) {
    if (VISCA_set_title_clear(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_irreceive_on") == 0) {
    if (VISCA_set_irreceive_on(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_irreceive_off") == 0) {
    if (VISCA_set_irreceive_off(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_irreceive_onoff") == 0) {
    if (VISCA_set_irreceive_onoff(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_home") == 0) {
    if (VISCA_set_pantilt_home(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_reset") == 0) {
    if (VISCA_set_pantilt_reset(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_limit_downleft_clear") == 0) {
    if (VISCA_set_pantilt_limit_downleft_clear(interface, camera)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_limit_upright_clear") == 0) {
    if (VISCA_set_pantilt_limit_upright_clear(interface, camera)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_datascreen_on") == 0) {
    if (VISCA_set_datascreen_on(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_datascreen_off") == 0) {
    if (VISCA_set_datascreen_off(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }  

  if (strcmp(command, "set_datascreen_onoff") == 0) {
    if (VISCA_set_datascreen_onoff(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
  
  if (strcmp(command, "set_power") == 0) {
#ifdef CHECK_VISCA_VALUES
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
#endif
    if (VISCA_set_power(interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
  
  if (strcmp(command, "set_keylock") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (boolarg == 03) {
      boolarg = 0;
    }
    if (VISCA_set_keylock (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_dzoom") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_dzoom (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_focus_auto") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_focus_auto (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_exp_comp_power") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_exp_comp_power (interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_slow_shutter_auto") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_slow_shutter_auto (interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_backlight_comp") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_backlight_comp (interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_zero_lux_shot") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_zero_lux_shot (interface, camera, boolarg) 
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_ir_led") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_ir_led (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_mirror") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_mirror (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_freeze") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_freeze (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_display") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_display (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_date_display") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_date_display (interface, camera, boolarg)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_time_display") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_time_display (interface, camera, boolarg)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_title_display") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_title_display (interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_zoom_tele_speed") == 0) {
    if ((arg1 == NULL) || (intarg1 < 2) || (intarg1 > 7)) {
      return 41;
    }
    if (VISCA_set_zoom_tele_speed (interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_zoom_wide_speed") == 0) {
    if ((arg1 == NULL) || (intarg1 < 2) || (intarg1 > 7)) {
      return 41;
    }
    if (VISCA_set_zoom_wide_speed(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_zoom_value") == 0) {
/* commented out for d70 
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1023)) {
      return 91;
      return 41;
    }
*/
    if (VISCA_set_zoom_value(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_focus_far_speed") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1023)) {
      return 41;
    }
    if (VISCA_set_focus_far_speed(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_focus_near_speed") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1023)) {
      return 41;
    }
    if (VISCA_set_focus_near_speed(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_focus_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1000) || (intarg1 > 40959)) {
      return 41;
    }
    if (VISCA_set_focus_value(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_focus_near_limit") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_focus_near_limit(interface, camera, intarg1) 
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_whitebal_mode") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 3)) {
      return 41;
    }
    if (VISCA_set_whitebal_mode(interface, camera, intarg1)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_rgain_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_rgain_value(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_bgain_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_bgain_value(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_shutter_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 27)) {
      return 41;
    }
    if (VISCA_set_shutter_value(interface, camera, intarg1)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_iris_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 17)) {
      return 41;
    }
    if (VISCA_set_iris_value(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_gain_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 7)) {
      return 41;
    }
    if (VISCA_set_gain_value(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_bright_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_bright_value(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_aperture_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_aperture_value(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_exp_comp_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_exp_comp_value(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_auto_exp_mode") == 0) {
    if (arg1 == NULL) {
      return 41;
    }
    
    if (!((intarg1 == 0) || (intarg1 == 3) || 
          (intarg1 == 10) || (intarg1 == 11) || (intarg1 == 13))) {
      return 41;
    }

    if (VISCA_set_auto_exp_mode(interface, camera, intarg1)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_wide_mode") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_wide_mode(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_picture_effect") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_picture_effect(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_digital_effect") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_digital_effect(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_digital_effect_level") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1)) {
      return 41;
    }
    if (VISCA_set_digital_effect_level(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "memory_set") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 5)) {
      return 41;
    }
    if (VISCA_memory_set(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "memory_recall") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 5)) {
      return 41;
    }
    if (VISCA_memory_recall(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "memory_reset") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 5)) {
      return 41;
    }
    if (VISCA_memory_reset(interface, camera, intarg1)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_zoom_and_focus_value") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 1023)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1000) || (intarg2 > 40959)) {
      return 42;
    }
    if (VISCA_set_zoom_and_focus_value(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

  if (strcmp(command, "set_pantilt_up") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_up(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_down") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_down(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_left") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_left(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_right") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_right(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_upleft") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_upleft(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_upright") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_upright(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_downleft") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_downleft(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_downright") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_downright(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_stop") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if (VISCA_set_pantilt_stop(interface, camera, intarg1, intarg2)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_limit_upright") == 0) {
 /* commented for D70 by MJ
    if ((arg1 == NULL) || (intarg1 < -879) || (intarg1 > 880)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < -299) || (intarg2 > 300)) {
      return 42;
    }
*/
    if (VISCA_set_pantilt_limit_upright(interface, camera, 
        intarg1, intarg2) != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_limit_downleft") == 0) {
 /* commented for D70 by MJ
    if ((arg1 == NULL) || (intarg1 < -879) || (intarg1 > 880)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < -299) || (intarg2 > 300)) {
      return 42;
    }
*/
    if (VISCA_set_pantilt_limit_downleft(interface, camera, 
        intarg1, intarg2) != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_absolute_position") == 0) {
/*
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if ((arg3 == NULL) || (intarg3 < -879) || (intarg3 > 880)) {
      return 43;
    }
    if ((arg4 == NULL) || (intarg4 < -299) || (intarg4 > 300)) {
      return 44;
    }
*/
    if (VISCA_set_pantilt_absolute_position(interface, camera, 
        intarg1, intarg2, intarg3, intarg4) != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_pantilt_relative_position") == 0) {
/*
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 24)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 20)) {
      return 42;
    }
    if ((arg3 == NULL) || (intarg3 < -879) || (intarg3 > 880)) {
      return 43;
    }
    if ((arg4 == NULL) || (intarg4 < -299) || (intarg4 > 300)) {
      return 44;
    }
*/
    if (VISCA_set_pantilt_relative_position(interface, camera, 
        intarg1, intarg2, intarg3, intarg4) != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

#if !D30ONLY
  if (strcmp(command, "set_title_params") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 600)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 0) || (intarg2 > 800)) {
      return 42;
    }
    if ((arg3 == NULL) || (intarg3 < 0) || (intarg3 > 32)) {
      return 43;
    }
    if ((arg4 == NULL) || (intarg4 < 0) || (intarg4 > 1)) {
      return 44;
    }
    temptitle = (VISCATitleData_t *)malloc((sizeof(unsigned int)*4)+
                                            sizeof(unsigned char*));
    temptitle->vposition=intarg1;
    temptitle->hposition=intarg2;
    temptitle->color=intarg3;
    temptitle->blink=intarg4;
    if (VISCA_set_title_params(interface, camera, temptitle) 
        != VISCA_SUCCESS) {
      free(temptitle);
      return 46;
    }
    free(temptitle);
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_date_time") == 0) {
    if ((arg1 == NULL) || (intarg1 < 1) || (intarg1 > 99)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 1) || (intarg2 > 12)) {
      return 42;
    }
    if ((arg3 == NULL) || (intarg3 < 1) || (intarg3 > 31)) {
      return 43;
    }
    if ((arg4 == NULL) || (intarg4 < 1) || (intarg4 > 23)) {
      return 44;
    }
    if ((arg5 == NULL) || (intarg5 < 1) || (intarg5 > 59)) {
      return 45;
    }
    if (VISCA_set_date_time(interface, camera, 
        intarg1, intarg2, intarg3, intarg4, intarg5) != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "set_title") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 600)) {
      return 41;
    }
    if ((arg2 == NULL) || (intarg2 < 0) || (intarg2 > 800)) {
      return 42;
    }
    if ((arg3 == NULL) || (intarg3 < 0) || (intarg3 > 32)) {
      return 43;
    }
    if ((arg4 == NULL) || (intarg4 < 0) || (intarg4 > 1)) {
      return 44;
    }
    if (arg5 == NULL) {
      return 45;
    }
    temptitle = (VISCATitleData_t *)malloc(sizeof(VISCATitleData_t));
    temptitle->vposition=intarg1;
    temptitle->hposition=intarg2;
    temptitle->color=intarg3;
    temptitle->blink=intarg4;
    strncpy(temptitle->title, arg5, 19);
    if (VISCA_set_title_params(interface, camera, temptitle) 
        != VISCA_SUCCESS) {
      free(temptitle);
      return 46;
    }
    free(temptitle);
    return 10;
  }
#endif

  if (strcmp(command, "get_power") == 0) {
    if (VISCA_get_power(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    if (value8 == 2) {
      *ret1 = 0;
    } else if (value8 == 3) {
      *ret1 = 1;
    } else {
      return 47;
    }
    return 11;
  }

  if (strcmp(command, "get_power") == 0) {
    if (VISCA_get_power(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    if (value8 == 2) {
      *ret1 = 1;
    } else if (value8 == 3) {
      *ret1 = 0;
    } else {
      return 47;
    }
    return 11;
  }

#if !D30ONLY
  if (strcmp(command, "get_dzoom") == 0) {
    if (VISCA_get_dzoom(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

  if (strcmp(command, "get_focus_auto") == 0) {
    if (VISCA_get_focus_auto(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    if (value8 == 2) {
      *ret1 = 1;
    } else if (value8 == 3) {
      *ret1 = 0;
    } else {
      return 47;
    }
    return 11;
  }

#if !D30ONLY
  if (strcmp(command, "get_exp_comp_power") == 0) {
    if (VISCA_get_exp_comp_power(interface, camera, &value8)
        != VISCA_SUCCESS){
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

  if (strcmp(command, "get_backlight_comp") == 0) {
    if (VISCA_get_backlight_comp(interface, camera, &value8)
        != VISCA_SUCCESS) {
      return 46;
    }
    if (value8 == 2) {
      *ret1 = 1;
    } else if (value8 == 3) {
      *ret1 = 0;
    } else {
      return 47;
    }
    return 11;
  }

#if !D30ONLY
  if (strcmp(command, "get_zero_lux_shot") == 0) {
    if (VISCA_get_zero_lux_shot(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_ir_led") == 0) {
    if (VISCA_get_ir_led(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_mirror") == 0) {
    if (VISCA_get_mirror(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_freeze") == 0) {
    if (VISCA_get_freeze(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_display") == 0) {
    if (VISCA_get_display(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

  if (strcmp(command, "get_datascreen") == 0) {
    if (VISCA_get_datascreen(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    if (value8 == 2) {
      *ret1 = 1;
    } else if (value8 == 3) {
      *ret1 = 0;
    } else {
      return 47;
    }
    return 11;
  }

  if (strcmp(command, "get_zoom_value") == 0) {
    if (VISCA_get_zoom_value(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

  if (strcmp(command, "get_focus_value") == 0) {
    if (VISCA_get_focus_value(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

#if !D30ONLY
  if (strcmp(command, "get_focus_auto_sense") == 0) {
    if (VISCA_get_focus_auto_sense(interface, camera, &value8)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_focus_near_limit") == 0) {
    if (VISCA_get_focus_near_limit(interface, camera, &value16)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }
#endif

  if (strcmp(command, "get_whitebal_mode") == 0) {
    if (VISCA_get_whitebal_mode(interface, camera, &value8)!=VISCA_SUCCESS){
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

#if !D30ONLY
  if (strcmp(command, "get_rgain_value") == 0) {
    if (VISCA_get_rgain_value(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_bgain_value") == 0) {
    if (VISCA_get_bgain_value(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }
#endif

  if (strcmp(command, "get_auto_exp_mode") == 0) {
    if (VISCA_get_auto_exp_mode(interface, camera, &value8)!=VISCA_SUCCESS){
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

#if !D30ONLY
  if (strcmp(command, "get_slow_shutter_auto") == 0) {
    if (VISCA_get_slow_shutter_auto(interface, camera, &value8)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

  if (strcmp(command, "get_shutter_value") == 0) {
    if (VISCA_get_shutter_value(interface, camera, &value16)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

  if (strcmp(command, "get_iris_value") == 0) {
    if (VISCA_get_iris_value(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

  if (strcmp(command, "get_gain_value") == 0) {
    if (VISCA_get_gain_value(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

#if !D30ONLY
  if (strcmp(command, "get_bright_value") == 0) {
    if (VISCA_get_bright_value(interface, camera, &value16)!=VISCA_SUCCESS){
      return 46;
    }
    *ret1 = value16;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_exp_comp_value") == 0) {
    if (VISCA_get_exp_comp_value(interface, camera, &value16)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_aperture_value") == 0) {
    if (VISCA_get_aperture_value(interface, camera, &value16)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_wide_mode") == 0) {
    if (VISCA_get_wide_mode(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_picture_effect") == 0) {
    if (VISCA_get_picture_effect(interface, camera, &value8)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_digital_effect") == 0) {
    if (VISCA_get_digital_effect(interface, camera, &value8)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }
#endif

#if !D30ONLY
  if (strcmp(command, "get_digital_effect_level") == 0) {
    if (VISCA_get_digital_effect_level(interface, camera, &value16)
        != VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }
#endif

  if (strcmp(command, "get_memory") == 0) {
    if (VISCA_get_memory(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_id") == 0) {
    if (VISCA_get_id(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

  if (strcmp(command, "get_videosystem") == 0) {
    if (VISCA_get_videosystem(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_pantilt_mode") == 0) {
    if (VISCA_get_pantilt_mode(interface, camera, &value16)!=VISCA_SUCCESS){
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

  if (strcmp(command, "get_pantilt_maxspeed") == 0) {
    if (VISCA_get_pantilt_maxspeed(interface, camera, &value8, &value8b)
        != VISCA_SUCCESS){
      return 46;
    }
    *ret1 = value8;
    *ret2 = value8b;
    return 12;
  }

  if (strcmp(command, "get_pantilt_position") == 0) {
    if (VISCA_get_pantilt_position(interface, camera, ret1, ret2)
        != VISCA_SUCCESS){
      return 46;
    }
    return 12;
  }

  if (strcmp(command, "set_at_mode_onoff") == 0) {
    if (VISCA_set_at_mode_onoff(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_ae_onoff") == 0) {
    if (VISCA_set_at_ae_onoff(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_autozoom_onoff") == 0) {
    if (VISCA_set_at_autozoom_onoff(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_atmd_framedisplay_onoff") == 0) {
    if (VISCA_set_atmd_framedisplay_onoff(interface, camera)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_frameoffset_onoff") == 0) {
    if (VISCA_set_at_frameoffset_onoff(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_atmd_startstop") == 0) {
    if (VISCA_set_atmd_startstop(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_chase_next") == 0) {
    if (VISCA_set_at_chase_next(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_mode_onoff") == 0) {
    if (VISCA_set_md_mode_onoff(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_frame") == 0) {
    if (VISCA_set_md_frame(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_detect") == 0) {
    if (VISCA_set_md_detect(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_lostinfo") == 0) {
    if (VISCA_set_at_lostinfo(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_lostinfo") == 0) {
    if (VISCA_set_md_lostinfo(interface, camera)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_measure_mode1_onoff") == 0) {
    if (VISCA_set_md_measure_mode1_onoff(interface, camera)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_measure_mode2_onoff") == 0) {
    if (VISCA_set_md_measure_mode2_onoff(interface, camera)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }
  
  if (strcmp(command, "set_focus_auto") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_focus_auto (interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_mode") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_at_mode(interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }


  if (strcmp(command, "set_at_ae") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_at_ae(interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }


  if (strcmp(command, "set_at_autozoom") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_at_autozoom(interface, camera, boolarg)!=VISCA_SUCCESS){
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_atmd_framedisplay") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_atmd_framedisplay(interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_frameoffset") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_at_frameoffset(interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_mode") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_md_mode(interface, camera, boolarg)!=VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_measure_mode1") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_md_measure_mode1(interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_measure_mode2") == 0) {
    if ((arg1 == NULL) || (boolarg == -1)) {
      return 41;
    }
    if (VISCA_set_md_measure_mode2(interface, camera, boolarg)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_wide_con_lens") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 7)) {
      return 41;
    }
    if (VISCA_set_wide_con_lens(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_chase") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 2)) {
      return 41;
    }
    if (VISCA_set_at_chase(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_at_entry") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 3)) {
      return 41;
    }
    if (VISCA_set_at_entry(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_adjust_ylevel") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 15)) {
      return 41;
    }
    if (VISCA_set_md_adjust_ylevel(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_adjust_huelevel") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 15)) {
      return 41;
    }
    if (VISCA_set_md_adjust_huelevel(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_adjust_size") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 15)) {
      return 41;
    }
    if (VISCA_set_md_adjust_size(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_adjust_disptime") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 15)) {
      return 41;
    }
    if (VISCA_set_md_adjust_disptime(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_adjust_refmode") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 2)) {
      return 41;
    }
    if (VISCA_set_md_adjust_refmode(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "set_md_adjust_reftime") == 0) {
    if ((arg1 == NULL) || (intarg1 < 0) || (intarg1 > 15)) {
      return 41;
    }
    if (VISCA_set_md_adjust_reftime(interface, camera, intarg1)
        != VISCA_SUCCESS) {
      return 46;
    }
    return 10;
  }

  if (strcmp(command, "get_keylock") == 0) {
    if (VISCA_get_keylock(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    if (value8 == 0) {
      *ret1 = 0;
    } else if (value8 == 2) {
      *ret1 = 1;
    } else {
      return 47;
    }
    return 11;
  }

  if (strcmp(command, "get_wide_con_lens") == 0) {
    if (VISCA_get_wide_con_lens(interface, camera, &value8)!=VISCA_SUCCESS){
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_atmd_mode") == 0) {
    if (VISCA_get_atmd_mode(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_at_mode") == 0) {
    if (VISCA_get_at_mode(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

  if (strcmp(command, "get_at_entry") == 0) {
    if (VISCA_get_at_entry(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_md_mode") == 0) {
    if (VISCA_get_md_mode(interface, camera, &value16)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value16;
    return 11;
  }

  if (strcmp(command, "get_md_ylevel") == 0) {
    if (VISCA_get_md_ylevel(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_md_huelevel") == 0) {
    if (VISCA_get_md_huelevel(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_md_size") == 0) {
    if (VISCA_get_md_size(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_md_disptime") == 0) {
    if (VISCA_get_md_disptime(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_md_refmode") == 0) {
    if (VISCA_get_md_refmode(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_md_reftime") == 0) {
    if (VISCA_get_md_reftime(interface, camera, &value8)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    return 11;
  }

  if (strcmp(command, "get_at_obj_pos") == 0) {
    if (VISCA_get_at_obj_pos(interface, camera, &value8, &value8b, &value8c)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    *ret2 = value8b;
    *ret3 = value8c;
    return 13;
  }

  if (strcmp(command, "get_md_obj_pos") == 0) {
    if (VISCA_get_md_obj_pos(interface, camera, &value8, &value8b, &value8c)!=VISCA_SUCCESS) {
      return 46;
    }
    *ret1 = value8;
    *ret2 = value8b;
    *ret3 = value8c;
    return 13;
  }

  /* If we reach this point, the commandline matched 
   * none of the commands we know
   */
  return 40;
}
