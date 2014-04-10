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

#ifndef _LIBVISCA_HL_H_
#define _LIBVISCA_HL_H_

#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>
#include <errno.h>
#include <termios.h>
#include "./libvisca.h"

int VISCA_doCommand(char *commandline, int *ret1, int *ret2, int *ret3,
    VISCAInterface_t *interface, VISCACamera_t *camera);

void VISCA_open_interface(VISCAInterface_t *interface, 
     VISCACamera_t *camera, char *ttydev);
void VISCA_close_interface(VISCAInterface_t *interface);

#endif
