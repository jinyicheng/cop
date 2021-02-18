<?php
/**
 * Credentials
 * PHP version 5
 *
 * @category Class
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */

namespace COP\Client;

use Psr\Http\Message\RequestInterface;

/**
 * Interface abstracting Credentials.
 *
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */
interface Credentials
{

    /**
     * Perform signature.
     *
     * @param RequestInterface $request Api request
     *
     * Returns request.
     */
    public function sign(RequestInterface $request);
}
