XDEBUG_FILE=/usr/lib64/php/modules/xdebug.so
XDEBUG_OPTIONS=-dzend_extension=$(XDEBUG_FILE) \
	-dxdebug.remote_enable=1 \
	-dxdebug.remote_host=192.168.1.101 \
	-dxdebug.remote_port=9000 \
	-dxdebug.remote_autostart=1 \
	-dxdebug.idekey=PHPSTORM \
	-dmemory_limit=2G

PHPUNIT_FILE=vendor/phpunit/phpunit/phpunit
FRAMEWORK_BOOTSTRAP= --bootstrap ./UnitTests/bootstrap.php

test:
	php $(PHPUNIT_FILE) $(XDEBUG_OPTIONS) $(FRAMEWORK_BOOTSTRAP) --testdox --colors=always --verbose ./UnitTests/Tests/*.php