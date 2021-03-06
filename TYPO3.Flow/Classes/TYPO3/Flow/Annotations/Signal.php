<?php
namespace TYPO3\Flow\Annotations;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Marks a method as a signal for the signal/slot implementation
 * of Flow. The method will be augmented as needed (using AOP)
 * to be a usable signal.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Signal
{
}
