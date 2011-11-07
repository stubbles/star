<?php
/**
 * Exception to be thrown in case something wents wrong with handlign star files.
 *
 * @package  star
 * @version  $Id$
 */
/**
 * Exception to be thrown in case something wents wrong with handlign star files.
 *
 * @package  star
 */
if (class_exists('StarException') === false) {
    class StarException extends Exception
    {
        // intentionally left empty
    }
}
?>