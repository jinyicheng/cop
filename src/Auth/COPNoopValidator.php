<?php
/**
 * COPValidator
 * PHP version 5
 *
 * @category Class
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */

namespace COP\Client\Auth;

use Psr\Http\Message\ResponseInterface;
use COP\Client\Validator;

/**
 * COPNoopValidator
 *
 * @category Class
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */
class COPNoopValidator implements Validator
{
    public function validate(ResponseInterface $response)
    {
        return true;
    }
    
}