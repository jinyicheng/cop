<?php
/**
 * Validator
 * PHP version 5
 *
 * @category Class
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */

namespace COP\Client;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface abstracting Validator.
 *
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */
interface Validator
{
    /**
     * Validate Response
     *
     * @param ResponseInterface $response Api response to validate
     *
     * @return bool
     */
    public function validate(ResponseInterface $response);
}
