<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 20/01/2019
	 * Time: 10:12 AM
	 */

	namespace Skyenet\Ajax;

	use ArrayAccess;
	use Skyenet\Http\ResponseCodes;
	use Skyenet\Skyenet;

	Class AjaxResponse implements ArrayAccess {
		private array $DATA = [];

		private bool $success = false;
		private ?string $message = null;

		private int $responseCode = ResponseCodes::HTTP_BAD_REQUEST;

		public function setMessage(?String $message): AjaxResponse {
			$this->message = $message;

			return $this;
		}

		public function setSuccessful(int $httpResponseCode = ResponseCodes::HTTP_OK): void {
			$this->success = true;
			$this->responseCode = $httpResponseCode;
		}

		public function __toString(): string {
			header('Content-Type: text/json');
			Skyenet::getInstance()
				   ->setResponseCode($this->responseCode);

			// provide a default error message if we are output with no message set and not marked as successful
			if ($this->message === null && !$this->success) {
				$this->message = 'An unknown error occurred whilst processing this request';
			}

			return (string)json_encode([
				'MESSAGE' => $this->message,
				'SUCCESS' => $this->success,
				'DATA' => $this->DATA
			], JSON_THROW_ON_ERROR, 512);
		}

		/**
		 * Whether a offset exists
		 *
		 * @link https://php.net/manual/en/arrayaccess.offsetexists.php
		 * @param mixed $offset <p>
		 * An offset to check for.
		 * </p>
		 * @return boolean true on success or false on failure.
		 * </p>
		 * <p>
		 * The return value will be casted to boolean if non-boolean was returned.
		 * @since 5.0.0
		 */
		public function offsetExists($offset): bool {
			return isset($this->DATA[$offset]);
		}

		/**
		 * Offset to retrieve
		 *
		 * @link https://php.net/manual/en/arrayaccess.offsetget.php
		 * @param mixed $offset <p>
		 * The offset to retrieve.
		 * </p>
		 * @return mixed Can return all value types.
		 * @since 5.0.0
		 */
		public function offsetGet($offset) {
			return $this->DATA[$offset] ?? null;
		}

		/**
		 * Offset to set
		 *
		 * @link https://php.net/manual/en/arrayaccess.offsetset.php
		 * @param mixed $offset <p>
		 * The offset to assign the value to.
		 * </p>
		 * @param mixed $value <p>
		 * The value to set.
		 * </p>
		 * @return void
		 * @since 5.0.0
		 */
		public function offsetSet($offset, $value): void {
			$this->DATA[$offset] = $value;
		}

		/**
		 * Offset to unset
		 *
		 * @link https://php.net/manual/en/arrayaccess.offsetunset.php
		 * @param mixed $offset <p>
		 * The offset to unset.
		 * </p>
		 * @return void
		 * @since 5.0.0
		 */
		public function offsetUnset($offset): void {
			unset($this->DATA[$offset]);
		}
	}
