<?php

namespace think\facade\captcha;

use Gregwar\Captcha\CaptchaBuilder;

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
        session('captcha', strtolower($builder->getPhrase()));
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
        return strtolower($code) === session('captcha');
    }
}
