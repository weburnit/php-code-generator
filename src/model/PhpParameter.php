<?php
/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace gossi\codegen\model;

use gossi\codegen\model\parts\NamePart;
use gossi\codegen\model\parts\TypePart;
use gossi\codegen\model\parts\ValuePart;
use gossi\docblock\Docblock;
use gossi\docblock\tags\ParamTag;

/**
 * Represents a PHP parameter.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Thomas Gossmann
 */
class PhpParameter extends AbstractModel implements ValueInterface {

	use NamePart;
	use TypePart;
	use ValuePart;

	private $passedByReference = false;

	/**
	 * Creates a new PHP parameter.
	 *
	 * @param string $name the parameter name
	 * @return static
	 */
	public static function create($name = null) {
		return new static($name);
	}

	/**
	 * Creates a PHP parameter from reflection
	 *
	 * @deprecated will be removed in version 0.5
	 * @param \ReflectionParameter $ref
	 * @return PhpParameter
	 */
	public static function fromReflection(\ReflectionParameter $ref) {
		$parameter = new static();
		$parameter->setName($ref->name)->setPassedByReference($ref->isPassedByReference());

		if ($ref->isDefaultValueAvailable()) {
			$value = $ref->getDefaultValue();
			
			if (is_string($value)
					|| is_int($value)
					|| is_float($value)
					|| is_bool($value)
					|| is_null($value)
					|| ($value instanceof PhpConstant)) {
				$parameter->setValue($value);
			} else {
				$parameter->setExpression($value);
			}
		}

		// find type and description in docblock
		$docblock = new Docblock($ref->getDeclaringFunction());

		$params = $docblock->getTags('param');
		$tag = $params->find($ref->name, function (ParamTag $t, $name) {
			return $t->getVariable() == '$' . $name;
		});

		if ($tag !== null) {
			$parameter->setType($tag->getType(), $tag->getDescription());
		}

		// set type if not found in comment
		if ($parameter->getType() === null) {
			if ($ref->isArray()) {
				$parameter->setType('array');
			} elseif ($class = $ref->getClass()) {
				$parameter->setType($class->getName());
			} elseif (method_exists($ref, 'isCallable') && $ref->isCallable()) {
				$parameter->setType('callable');
			}
		}

		return $parameter;
	}

	/**
	 * Creates a new PHP parameter
	 *
	 * @param string $name the parameter name
	 */
	public function __construct($name = null) {
		$this->setName($name);
	}

	/**
	 * Sets whether this parameter is passed by reference
	 *
	 * @param bool $bool `true` if passed by reference and `false` if not
	 * @return $this
	 */
	public function setPassedByReference($bool) {
		$this->passedByReference = (boolean) $bool;

		return $this;
	}

	/**
	 * Returns whether this parameter is passed by reference
	 *
	 * @return bool `true` if passed by reference and `false` if not
	 */
	public function isPassedByReference() {
		return $this->passedByReference;
	}

	/**
	 * Returns a docblock tag for this parameter
	 *
	 * @return ParamTag
	 */
	public function getDocblockTag() {
		return ParamTag::create()
			->setType($this->getType())
			->setVariable($this->getName())
			->setDescription($this->getTypeDescription());
	}

	/**
	 * Alias for setDescription()
	 *
	 * @see #setDescription
	 * @param string $description
	 * @return $this
	 */
	public function setTypeDescription($description) {
		return $this->setDescription($description);
	}

	/**
	 * Alias for getDescription()
	 *
	 * @see #getDescription
	 * @return string
	 */
	public function getTypeDescription() {
		return $this->getDescription();
	}
}
