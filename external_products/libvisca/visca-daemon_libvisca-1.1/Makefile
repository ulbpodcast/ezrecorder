all: visca-cli visca-daemon

clean:
	rm -v *.o 

distclean:
	rm -v *.o 
	rm -v visca-cli visca-daemon

visca-cli: visca-cli.c libvisca_hl.o
	gcc -Wall -o visca-cli visca-cli.c ../libvisca-1.1.0/visca/.libs/libvisca.a libvisca_hl.o

visca-daemon: visca-daemon.c libvisca_hl.o sockhelp.o
	gcc -Wall -o visca-daemon visca-daemon.c ../libvisca-1.1.0/visca/.libs/libvisca.a \
	    libvisca_hl.o sockhelp.o 

libvisca_hl.o: libvisca_hl.c
	gcc -Wall -c libvisca_hl.c

sockhelp.o: sockhelp.h sockhelp.c 
	gcc -Wall -c sockhelp.c 
