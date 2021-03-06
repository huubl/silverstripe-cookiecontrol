<?php

namespace Hestec\CookieControl;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\Debug;
use SilverStripe\View\Requirements;
use SilverStripe\i18n\i18n;
use SilverStripe\SiteConfig\SiteConfig;

class CookieControlExtension extends Extension
{

    public function onAfterInit(){

        // if CookieControl is enabled in the CMS site settings, add the js and css
        if ($this->CookieControl()) {

            Requirements::css("resources/hestec/silverstripe-cookiecontrol/client/css/style.css");

            Requirements::combine_files(
                'cookiecontrol.js',
                [
                    'resources/hestec/silverstripe-cookiecontrol/client/javascript/js.cookie.js',
                    'resources/hestec/silverstripe-cookiecontrol/client/javascript/script.js',
                    'resources/hestec/silverstripe-cookiecontrol/client/javascript/templates.js',
                    'resources/hestec/silverstripe-cookiecontrol/client/javascript/lang/en.js'
                ]
            );

            if ($this->LangFile()) {
                Requirements::javascript($this->LangFile());
            }

            Requirements::customScript($this->CookieControl());

        }

        // cookies are removed by javascript, this is just a test for doing it by php
        //Cookie::force_expiry('_ga', '', '.hst1.nl');

    }

    public function LangFile(){

        $docroot = realpath($_SERVER['DOCUMENT_ROOT']);
        $langfile = substr(i18n::get_locale(), 0, 2).".js";
        //$langfile = i18n::get_lang_from_locale(i18n::get_locale()).".js";
        if (file_exists($docroot."/app/lang/cookiecontrol/".$langfile)){
            return "app/lang/cookiecontrol/".$langfile;
        }
        //elseif (file_exists($docroot."/vender/hestec/silverstripe-cookiecontrol/lang/".$langfile)){
        elseif (file_exists($docroot."/resources/hestec/silverstripe-cookiecontrol/client/javascript/lang/".$langfile)){
            return "resources/hestec/silverstripe-cookiecontrol/client/javascript/lang/".$langfile;
        }
        return false;

    }

    public function CookieControl(){

        $siteConfig = SiteConfig::current_site_config();
        if ($siteConfig->CcEnable) {

            $expiration = 30;
            if ($siteConfig->CcExpiration <> $expiration && $siteConfig->CcExpiration > 0) {
                $expiration = $siteConfig->CcExpiration;
            }

            $timeout = 500;
            if ($siteConfig->CcTimeOut <> $timeout && $siteConfig->CcTimeOut > 0) {
                $timeout = $siteConfig->CcTimeOut;
            }
            $locale = "en";
            if ($this->LangFile()){
                $locale = substr(i18n::get_locale(), 0, 2);
            }
            $implicit = "false";
            if ($siteConfig->CcImplicit == true) {
                $implicit = "true";
            }

            $script = "gdprCookieNotice({";
            $script .= "locale: '".$locale."',";
            $script .= "timeout: $timeout,";
            $script .= "expiration: $expiration,";
            //$script .= "domain: '" . $_SERVER['HTTP_HOST'] . "',";
            $script .= "domain: '".CookieControl::getCookieDomain()."',";
            //$script .= "implicit: $implicit,";
            $script .= "statement: '".$siteConfig->CcStatement()->AbsoluteLink()."',";
            if ($siteConfig->CcPerformance == true){
                $script .= "performance: ['".$this->getCookiesFromConfig('PerformanceCookies')."'],";
            }
            if ($siteConfig->CcAnalytics == true) {
                $script .= "analytics: ['".$this->getCookiesFromConfig('AnalyticsCookies')."'],";
            }
            if ($siteConfig->CcMarketing == true) {
                $script .= "marketing: ['". $this->getCookiesFromConfig('MarketingCookies')."']";
            }
            $script .= "});";

            return $script;

        }

        return false;

    }

    public function getCookiesFromConfig($type){

        return str_replace(",", "','", str_replace(' ', '', CookieControl::getCookies($type)));

    }

    /*public function getModulePath(){

        return str_replace(realpath($_SERVER['DOCUMENT_ROOT'])."/", '', dirname(__DIR__));

    }*/

}
