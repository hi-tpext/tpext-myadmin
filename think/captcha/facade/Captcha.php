<?php

namespace think\captcha\facade;

use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;
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
        $phraseB = new PhraseBuilder(4);
        $builder = new CaptchaBuilder(null, $phraseB);
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
