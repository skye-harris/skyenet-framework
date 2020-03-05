<?php

	namespace CommandLine\Modules;

	use CommandLine\CliModule;

	class SeLinuxModule extends CliModule {
		public const DESCRIPTION = 'Add and Remove SeLinux permissions for web process write access';
		public const MODULE_NAME = 'selinux';

		protected array $longOpts = [
			'set-web-writeable:' => ['<dirname>', 'Mark the contents of the directory as being writeable by web processes'],
			'set-web-readonly:' => ['<dirname>', 'Mark the contents of the directory as being readonly by web processes'],
		];

		public function set_web_writeable(string $option): void {
			$filePath = realpath($option);

			if (!file_exists($filePath) || !is_dir($filePath))
				return;

			/** @noinspection SpellCheckingInspection */
			$seManageCommand = "semanage fcontext -a -t httpd_sys_rw_content_t \"{$filePath}(/.*)?\"";

			/** @noinspection SpellCheckingInspection */
			$restoreCommand = "restorecon -R -v {$filePath}";

			$this->shellExecute($seManageCommand);
			$this->shellExecute($restoreCommand);
		}

		public function set_web_readonly(string $option): void {
			$filePath = realpath($option);

			if (!file_exists($filePath) || !is_dir($filePath))
				return;

			/** @noinspection SpellCheckingInspection */
			$seManageCommand = "semanage fcontext -d -t httpd_sys_rw_content_t \"{$filePath}(/.*)?\"";

			/** @noinspection SpellCheckingInspection */
			$restoreCommand = "restorecon -R -v {$filePath}";

			$this->shellExecute($seManageCommand);
			$this->shellExecute($restoreCommand);
		}
	}