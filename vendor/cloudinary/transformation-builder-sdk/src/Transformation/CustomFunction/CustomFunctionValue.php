<?php
/**
 * This file is part of the Cloudinary PHP package.
 *
 * (c) Cloudinary
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cloudinary\Transformation;

/**
 * Class CustomFunctionValue
 */
class CustomFunctionValue extends QualifierMultiValue
{
    /**
     * @var array $argumentOrder The order of the arguments.
     */
    protected array $argumentOrder = ['preprocess', 'type', 'source'];

    /**
     * CustomFunctionValue constructor.
     *
     * @param mixed      $source
     * @param mixed|null $type
     * @param mixed|null $preprocess
     */
    public function __construct($source = null, mixed $type = null, mixed $preprocess = null)
    {
        parent::__construct();

        $this->setSimpleValue('source', $source);
        $this->setSimpleValue('type', $type);
        $this->setSimpleValue('preprocess', $preprocess);
    }
}
