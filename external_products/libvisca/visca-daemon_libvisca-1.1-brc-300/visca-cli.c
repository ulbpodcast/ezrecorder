/*
 * Command line interface to the VISCA(tm) Camera Control Library
 * based on the VISCA(tm) Camera Control Library Test Program
 * by Damien Douxchamps 
 * 
 * Copyright (C) 2003,2004 Simon Bichler 
 * 
 * Written by Simon Bichler <bichlesi@in.tum.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

#include <stdlib.h>
#include <stdio.h>
#include <unistd.h> /* UNIX standard function definitions */
#include <fcntl.h> /* File control definitions */
#include <errno.h> /* Error number definitions */
#include <termios.h> /* POSIX terminal control definitions */
#include <sys/ioctl.h>
#include <string.h>

#include "libvisca.h"
#include "libvisca_hl.h" /*Highlevel functions for VISCA protocol*/
#define DEBUG 0

#define D30ONLY 0

/*The device the camera is attached to*/
char *ttydev = "/dev/ttyS0";

/*Structures needed for the VISCA library*/
VISCAInterface_t interface;
VISCACamera_t camera;

/*print usage message and exit*/
void print_usage() {
  fprintf(stderr,"Usage: visca-cli [-d <serial port device>] command\n");
  fprintf(stderr,"  default serial port device: /dev/ttyS0\n");      
  fprintf(stderr,"  for available commands see sourcecode...\n");
  exit(1);  
}

/* This routine find the device the camera is attached to (if specified)
 * It concatenates the rest of the commandline and returnes that string
 */
char *process_commandline(int argc, char **argv) {
  /*loop counter*/
  int i;

  /*temporarily used to hold the length of a string*/
  int length = 0;
  
  /*used to hold the commandline that is returned*/
  char *commandline;
  
  /*at least a command has to be specified*/
  if (argc < 2) {
    print_usage();
  }

  /*Find the ttydev if specified*/
  if (strncmp(argv[1], "-d", 2) == 0) {
    /*after the -d and the device name at least one command has to follow*/
    if (argc < 4) {
      print_usage();
    } else {
      ttydev = argv[2];
      /*we have used up two arguments*/
      argv += 2;
      argc -= 2;
    }
  }
  
  /*concatenate command string*/

  /*find total length of commandline*/
  length = 0;
  for (i=1; i < argc; i++) {
    length += strlen(argv[i])+1;
  }
  
  /*allocate memory for commandline*/
  commandline = (char *)malloc(sizeof(char) * length);
  
  /*copy the first argument to the commandline*/
  strcpy(commandline, argv[1]);

  /*add the rest of the arguments, seperated by blanks*/
  for (i=2; i < argc; i++) {
    strcat(commandline, " ");
    strcat(commandline, argv[i]);
  }
  
  return commandline;  
}

int main(int argc, char **argv) {
  char *commandline;
  int errorcode, ret1, ret2, ret3;

  commandline = process_commandline(argc, argv);
  
  /*open camera interface*/
  VISCA_open_interface(&interface, &camera, ttydev);

  errorcode = VISCA_doCommand(commandline, &ret1, &ret2, &ret3, &interface, &camera);

  switch(errorcode) {
    case 10:
      printf("10 OK - no return value\n");
      break;
    case 11:
      printf("11 OK - one return value\nRET1: %i\n", ret1);
      break;    
    case 12:
      printf("12 OK - two return values\nRET1: %i\nRET2: %i\n", ret1, ret2);
      break;
    case 13:
      printf("13 OK - three return values\nRET1: %i\nRET2: %i\nRET3: %i\n", 
             ret1, ret2, ret3);
      break;
    case 40:
      printf("40 ERROR - command not recognized\n");
      break;
    case 41:
      printf("41 ERROR - argument 1 not recognized\n");
      break;
    case 42:
      printf("42 ERROR - argument 2 not recognized\n");
      break;
    case 43:
      printf("43 ERROR - argument 3 not recognized\n");
      break;
    case 44:
      printf("44 ERROR - argument 4 not recognized\n");
      break;
    case 45:
      printf("45 ERROR - argument 5 not recognized\n");
      break;
    case 46:
      printf("46 ERROR - camera replied with an error\n");
      break;
    case 47:
      printf("47 ERROR - camera replied with an unknown return value\n");
      break;
    default:
      printf("unknown error code: %i\n", errorcode);
  }

  VISCA_close_interface(&interface);
  exit(0);
}
