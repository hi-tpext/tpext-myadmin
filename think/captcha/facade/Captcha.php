<?php

namespace think\captcha\facade;

use Gregwar\Captcha\CaptchaBuilder;
use think\facade\Session;

class Captcha
{
    /**
     * Undocumented function
     *
     * @return mixed
     */
    public static function create()
    {
        $builder = new CaptchaBuilder;
        $builder->build();
        Session::set('captcha', strtolower($builder->getPhrase()));
        return response($builder->get(), 200, ['Content-Type' => 'image/jpeg']);
    }

    /**
     * Undocumented function
     *
     * @param string $code
     * @return boolean
     */
    public static function check(string $code)
    {
        return strtolower($code) === Session::get('captcha');
    }
}
