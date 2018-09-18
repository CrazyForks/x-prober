<?php

namespace InnStudio\Prober\I18n;

class I18nApi
{
    public static function _($str)
    {
        static $translation = null;

        if (null === $translation) {
            $translation = \json_decode(\base64_decode(\LANG), true);
        }

        $clientLang = self::getClientLang();

        $output = isset($translation[$clientLang][$str]) ? $translation[$clientLang][$str] : $str;

        return $output ?: $str;
    }

    public static function getClientLang()
    {
        static $cache = null;

        if (null !== $cache) {
            return $cache;
        }

        if ( ! isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $cache = '';

            return $cache;
        }

        $client = \explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        if (isset($client[0])) {
            $cache = \str_replace('-', '_', $client[0]);
        } else {
            $cache = 'en';
        }

        return $cache;
    }
}
