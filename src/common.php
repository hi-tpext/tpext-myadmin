<?php

/**
 * Undocumented function
 *
 * @param string $pwd
 * @return array
 */
function passCrypt($pwd)
{
    $pwd = md5($pwd);
    $salt = substr($pwd, 10, 5);
    $pwd = md5($salt . $pwd . $salt);
    return [$pwd, $salt];
}
