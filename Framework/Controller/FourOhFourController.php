<?php

	namespace Skyenet\Controller;

	use Skyenet\Http\ResponseCodes;

	/**
	 * Created by PhpStorm.
	 * User: skye
	 * Date: 31/05/2017
	 * Time: 3:45 PM
	 */
	class FourOhFourController extends Controller {
		public function get(): void {
			$this->skyenet->setResponseCode(ResponseCodes::HTTP_NOT_FOUND);
		}
	}