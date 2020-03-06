XDEBUG_IP=192.168.1.101
XDEBUG_IDE_KEY=SKYENET_FRAMEWORK
XDEBUG_FILE=/usr/lib64/php/modules/xdebug.so
XDEBUG_OPTIONS=-dzend_extension=$(XDEBUG_FILE) \
	-dxdebug.remote_enable=1 \
	-dxdebug.remote_host=$(XDEBUG_IP) \
	-dxdebug.remote_port=9000 \
	-dxdebug.remote_autostart=1 \
	-dxdebug.idekey=$(XDEBUG_IDE_KEY)

PHPUNIT_FILE=vendor/phpunit/phpunit/phpunit
FRAMEWORK_BOOTSTRAP= --bootstrap ./UnitTests/bootstrap.php

test:
	php $(PHP_ARGS) $(PHPUNIT_FILE) $(FRAMEWORK_BOOTSTRAP) --testdox --colors=always --verbose --testdox-text TestResults.txt ./UnitTests/Tests

test-debug:
	make test PHP_ARGS="$(XDEBUG_OPTIONS)"

cli-debug:
	php $(XDEBUG_OPTIONS) skyenet-cli.php