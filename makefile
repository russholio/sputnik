all:
	composer install

install:
	./create_bin_script

clean:
	rm -fR composer.lock vendor/ bin/ ~/.composer/cache/
