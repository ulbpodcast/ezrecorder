/*
 * Network daemon for VISCA(tm) Camera Control Library
 * based on the VISCA(tm) Camera Control Library Test Program
 * by Damien Douxchamps 
 * 
 * Copyright (C) 2003 Simon Bichler 
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

/*
Usage:
======
This network daemon binds to port 3376 (0xD30) and waits for a client to connect
and to send commands. The commands are then sent to a camera connected via a
serial cable, and a response is generated.
*/

#include <stdlib.h>
#include <stdio.h>
#include <sys/wait.h>
#include <unistd.h> /* UNIX standard function definitions */
#include <fcntl.h> /* File control definitions */
#include <errno.h> /* Error number definitions */
#include <termios.h> /* POSIX terminal control definitions */
#include <sys/ioctl.h>
#include <string.h>
#include <pwd.h>      /* To authenticate user */

#include <syslog.h>
#include <sys/types.h>
#include <sys/time.h>
#include <sys/resource.h>
#include <sys/wait.h>
#include <sys/stat.h>

#include "libvisca.h"
#include "libvisca_hl.h" /*Highlevel functions for VISCA protocol*/
#include "sockhelp.h" /*tools for socket handling from the socket-faq*/

#define DEBUG 0

#define D30ONLY 0

#ifndef TRUE
#define TRUE   1
#endif

#ifndef FALSE
#define FALSE   0
#endif

/*Global variables, necessary for signal handling*/
/*Interface for the libVISCA*/
VISCAInterface_t interface;
VISCACamera_t camera;

/*sockets used for networking*/
int listensock = -1; /* So that we can close sockets on ctrl-c */
int connectsock = -1;

int background = FALSE;

/*This waits for all children, so that they don't become zombies. */
/*Taken from the socket-faq*/
void sig_chld(int signal_type) {
  int pid;
  int status;
  while ((pid = wait3(&status, WNOHANG, NULL)) > 0);
}

/*close the sockets and the camera interface in case of shutdown*/
void sig_abort(int signal_type) {
  close(listensock);
  close(connectsock);
  VISCA_close_interface(&interface);
  /*Remove lock file*/
  unlink("/var/lock/visca-daemon.lock");
  if (background == TRUE) {
    syslog(LOG_INFO, "Visca Camera daemon halted.\n");
  } else {
    fprintf(stderr, "Visca Camera daemon halted.\n");
  } 
  exit(0);
}

/*print usage message and exit*/
void print_usage() {
  fprintf(stderr,"Usage: visca-daemon [-s <serial port device>] [-p <tcp/ip port>] [-d] [-v]\n");
  fprintf(stderr,"  default serial port device: /dev/ttyS0\n");
  fprintf(stderr,"  default tcp/ip port: 3376 (0xD30)\n");
  fprintf(stderr,"  -d sends the visca-daemon to daemon mode\n");
  fprintf(stderr,"  -v makes the visca-daemon tell about every command it executes\n\n");  
  exit(1);  
}

int main(int argc, char **argv) {

  /*Set default values for tty device, server port and background operation*/
  char ttydev[1024];
  strncpy(ttydev, "/dev/ttyS0", 1023);
  char serverport[1024];
  strncpy(serverport, "3376", 1023);
  int auth = FALSE;
  int verbose = FALSE;
  
  /*Find options on the commandline*/
  int c;
  extern char *optarg;
  
  while ((c = getopt(argc, argv, "s:p:dv")) != -1) {
    switch(c) {
      case 's':
        strncpy(ttydev, optarg, 1023);
        break;
      case 'p':
        strncpy(serverport, optarg, 1023);
	break;
      case 'd':
        background = TRUE;
	break;
      case 'a':
        auth = TRUE;
	break;
      case 'v':
        verbose = TRUE;
	break;
      case '?':
        print_usage();
    }
  }
  
  /*open camera interface*/
  VISCA_open_interface(&interface, &camera, ttydev);
  
  /*Set up some signal handling*/
  ignore_pipe();
  struct sigaction sa;  
  sigemptyset(&sa.sa_mask);
  sa.sa_flags = 0;
  sa.sa_handler = sig_chld;
  sigaction(SIGCHLD, &sa, NULL);
  sa.sa_handler = sig_abort;
  sigaction(SIGINT, &sa, NULL);
  sigaction(SIGQUIT, &sa, NULL);
  sigaction(SIGTERM, &sa, NULL);

  /*initialize network stuff - taken from socket-faq*/
  int port = -1;
  port = atoport(serverport, "tcp");
  if (port == -1) {
    fprintf(stderr,"Unable to find service: %s\n",serverport);
    exit(1);
  }

  /*check for lock file*/
  struct stat tempstat;
  if (stat("/var/lock/visca-daemon.lock", &tempstat) == 0) {
    fprintf(stderr, "Lockfile /var/lock/visca-daemon.lock exists\nMaybe another visca-daemo is running already?\n\n");
    exit(1);      
  }

  /*go to background if the user wants us to*/
  if (background == TRUE) {
    if(daemon(0,0) != 0) {
      fprintf(stderr,"Unable to detach from console\n");
      exit(1);
    }
    openlog("visca-daemon", 0, LOG_DAEMON);
  }

  mknod("/var/lock/visca-daemon.lock", S_IFREG, 0);
  
  if (background == TRUE) {
    syslog(LOG_INFO, "Visca Camera daemon started.\n");
  } else {
    fprintf(stderr, "Visca Camera daemon started.\n");
  } 
  
  /*Wait for a client to make connections*/
  /*forking is handled by sockhelp.c from the socket-faq*/
  int sock = get_connection(SOCK_STREAM, port, &listensock);
  connectsock = sock;
  if (background == TRUE) {
    syslog(LOG_INFO, "New connection established\n");
  } else {
    fprintf(stderr, "New connection established\n");
  } 

  /*Greet the user*/
  char buffer[1024];
  sock_puts(sock,"Welcome to the VISCA camera server\n");
  
  int connected = 1;
  int errorcode;
  int ret1, ret2, ret3;
  while (connected) {
    /* Read input */
    if (sock_gets(sock, buffer, 1024) < 0) {
      connected = 0;
    } else if (buffer[0]=='\0') {
      connected = 0;    
    } else {
      if (verbose == TRUE) {
        if (background == TRUE) {
          syslog(LOG_INFO, "Command received: %s\n", buffer);
	} else {
          fprintf(stderr, "Command received: %s\n", buffer);
	}
      }
      errorcode = VISCA_doCommand(buffer, &ret1, &ret2, &ret3, &interface, &camera);
      switch(errorcode) {
        case 10:
	  snprintf(buffer, 1023, "10 OK - no return value\n");
          break;
        case 11:
	  snprintf(buffer, 1023, "11 OK - one return value\nRET1: %i\n", ret1);
          break;    
        case 12:
	  snprintf(buffer, 1023, "12 OK - two return values\nRET1: %i\nRET2: %i\n", ret1, ret2);
          break;
        case 13:
	  snprintf(buffer, 1023, "13 OK - three return values\nRET1: %i\nRET2: %i\nRET3: %i\n", ret1, ret2, ret3);
          break;
        case 40:
	  snprintf(buffer, 1023, "40 ERROR - command not recognized\n");
          break;
        case 41:
	  snprintf(buffer, 1023, "41 ERROR - argument 1 not recognized\n");
          break;
        case 42:
	  snprintf(buffer, 1023, "42 ERROR - argument 2 not recognized\n");
          break;
        case 43:
	  snprintf(buffer, 1023, "43 ERROR - argument 3 not recognized\n");
          break;
        case 44:
	  snprintf(buffer, 1023, "44 ERROR - argument 4 not recognized\n");
          break;
        case 45:
	  snprintf(buffer, 1023, "45 ERROR - argument 5 not recognized\n");
          break;
        case 46:
	  snprintf(buffer, 1023, "46 ERROR - camera replied with an error\n");
          break;
        case 47:
	  snprintf(buffer, 1023, "47 ERROR - camera replied with an unknown return value\n");
          break;
        default:
	  snprintf(buffer, 1023, "unknown error code: %i\n", errorcode);
      }
      if (verbose == TRUE) {
        if (background == TRUE) {
          syslog(LOG_INFO, "Answer sent: %s", buffer);
	} else {
          fprintf(stderr, "Answer sent: %s", buffer);
	}
      }

      if (sock_puts(sock, buffer) < 0) {
        connected = 0;
      }
    }
  }
  if (background == TRUE) {
    syslog(LOG_INFO, "Connection closed\n");
  } else {
    fprintf(stderr, "Connection closed\n");
  }
  close(sock);
  return 0;
}
