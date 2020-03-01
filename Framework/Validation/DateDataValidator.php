<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:32 PM
	 */

	namespace Skyenet\Validation;

	class DateDataValidator extends TypeDataValidator {
		private ?int $day = null;
		private ?int $month = null;
		private ?int $year = null;

		//todo: ideally this should use the rawValue property
		public function __construct(?int $day, ?int $month, ?int $year, string $varName) {
			parent::__construct(null,$varName);

			$this->day = $day;
			$this->month = $month;
			$this->year = $year;
		}

		public function valueStringDMY(): ?String {
			return $this->day ? sprintf("%'.02d-%'.02d-%'.04d",$this->day,$this->month,$this->year) : null;
		}

		public function valueStringYMD(): ?String {
			return $this->day ? sprintf("%'.04d-%'.02d-%'.02d",$this->year,$this->month,$this->day) : null;
		}

		public function valueArrayDMY(): ?array {
			return $this->day ? [
				$this->day,
				$this->month,
				$this->year,
			] : null;
		}

		public function valueArrayYMD(): ?array {
			return $this->day ? [
				$this->year,
				$this->month,
				$this->day,
			] : null;
		}
	}