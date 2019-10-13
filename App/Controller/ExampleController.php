<?php
	namespace App\Controller;

	use Skyenet\Ajax\AjaxResponse;
	use Skyenet\Controller\Controller;
	use Skyenet\View\View;

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 5/09/2019
	 * Time: 7:41 am
	 */
	class ExampleController extends Controller {
		protected function buildPage(string $pageContent): void {
			$view = new View('Views/Page');

			echo $view->buildOutput([
				'title' => 'Skyenet Example',
				'body' => $pageContent
			]);
		}

		public function getRequest(string $variable): void {
			$this->buildPage('hello world');
		}

		public function postRequest(): void {
			$ajaxResponse = new AjaxResponse();

			echo $ajaxResponse;
		}
	}