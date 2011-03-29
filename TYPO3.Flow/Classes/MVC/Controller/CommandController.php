<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A controller which processes requests from the command line
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class CommandController implements ControllerInterface {

	/**
	 * @var \F3\FLOW3\MVC\CLI\Request
	 */
	protected $request;

	/**
	 * @var \F3\FLOW3\MVC\CLI\Response
	 */
	protected $response;

	/**
	 * @var \F3\FLOW3\MVC\Controller\Arguments
	 */
	protected $arguments;

	/**
	 * Constructs the controller
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->arguments = new Arguments(array());
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canProcessRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		return $request instanceof \F3\FLOW3\MVC\CLI\Request;
	}

	/**
	 * Processes a command line request.
	 *
	 * @param \F3\FLOW3\MVC\CLI\Request $request The request object
	 * @param \F3\FLOW3\MVC\CLI\Response $response The response, modified by this controller
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '".' , 1300787096);

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->commandMethodName = $this->resolveCommandMethodName();
#		$this->mapRequestArgumentsToControllerArguments();
		$this->callCommandMethod();
	}

	/**
	 * Resolves and checks the current command method name
	 *
	 * @return string Method name of the current command
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveCommandMethodName() {
		$commandMethodName = $this->request->getControllerCommandName() . 'Command';
		if (!is_callable(array($this, $commandMethodName))) {
			throw new \F3\FLOW3\MVC\Exception\NoSuchCommandException('A command method "' . $commandMethodName . '()" does not exist in controller "' . get_class($this) . '".', 1300902143);
		}
		return $commandMethodName;
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		$optionalArgumentNames = array();
		$allArgumentNames = $this->arguments->getArgumentNames();
		foreach ($allArgumentNames as $argumentName) {
			if ($this->arguments[$argumentName]->isRequired() === FALSE) $optionalArgumentNames[] = $argumentName;
		}

		$validator = $this->objectManager->get('F3\FLOW3\MVC\Controller\ArgumentsValidator');
		$this->propertyMapper->mapAndValidate($allArgumentNames, $this->request->getArguments(), $this->arguments, $optionalArgumentNames, $validator);

		$this->argumentsMappingResults = $this->propertyMapper->getMappingResults();
	}

	/**
	 * Calls the specified command method and passes the arguments.
	 *
	 * If the command returns a string, it is appended to the content in the
	 * response object. If the command doesn't return anything and a valid
	 * view exists, the view is rendered automatically.
	 *
	 * @param string $commandMethodName Name of the command method to call
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function callCommandMethod() {
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}

		$commandResult = call_user_func_array(array($this, $this->commandMethodName), $preparedArguments);

		if (is_string($commandResult) && strlen($commandResult) > 0) {
			$this->response->appendContent($commandResult);
		} elseif (is_object($commandResult) && method_exists($commandResult, '__toString')) {
			$this->response->appendContent((string)$commandResult);
		}
	}

}

?>