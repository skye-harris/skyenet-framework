<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 21/09/2019
	 * Time: 1:36 pm
	 */

	namespace Skyenet\Traits;

	use ReflectionClass;
	use ReflectionException;

	trait TraitInitialiser {
		protected function getShortClassName(String $fullyQualifiedClassName): string {
			return substr($fullyQualifiedClassName, strrpos($fullyQualifiedClassName, '\\') + 1);
		}

		protected function discoverTraitInitialisers(ReflectionClass $reflectionClass, array &$resultArray): void {
			$classTraits = $reflectionClass->getTraits();
			foreach ($classTraits AS $classTrait) {
				$initMethod = lcfirst($this->getShortClassName($classTrait->name));

				if ($classTrait->hasMethod($initMethod) && !in_array($initMethod, $resultArray, true)) {
					$resultArray[] = $initMethod;
				}

				$this->discoverTraitInitialisers($classTrait, $resultArray);
			}

			if ($parentClass = $reflectionClass->getParentClass()) {
				$this->discoverTraitInitialisers($parentClass, $resultArray);
			}
		}

		public function initialiseTraits(): void {
			try {
				$discoveredTraits = [];

				$reflectionClass = new ReflectionClass(get_class($this));
				$this->discoverTraitInitialisers($reflectionClass, $discoveredTraits);

				foreach ($discoveredTraits AS $discoveredTrait => $initFunc) {
					$this->$initFunc();
				}
			} catch (ReflectionException $exception) {

			}
		}
	}