<?php

use app\tests\interfaces\VoteInterfaces;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor implements VoteInterfaces
{
    use _generated\AcceptanceTesterActions;

    /**
     * 因應環境不同，取得不同url
     *
     * @param  string $env
     * @param  string $page
     * @return mixed
     */
    public function amOnEnvPage($env, $page)
    {
        switch ($env) {
            case 'ws':
                $this->amOnPage($page);
                break;
            case 'alpha':
                $this->amOnPage(str_replace('web/', '', $page));
                break;
            default:
                $this->amOnPage($page);
                break;
        }
    }

    /**
     * 因應環境不同，取得不同url
     *
     * @param  string $env
     * @param  string $page
     * @return mixed
     */
    public function seeInEnvCurrentUrl($env, $url)
    {
        switch ($env) {
            case 'ws':
                $this->seeInCurrentUrl($url);
                break;
            case 'alpha':
                $this->seeInCurrentUrl(str_replace('web/', '', $url));
                break;
            default:
                $this->seeInCurrentUrl($url);
                break;
        }
    }

    /**
     * prompt輸入時，不顯示輸入的字元
     * 
     * https://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
     */
    public function getObscuredText()
    {
        readline_callback_handler_install('', function(){});
        $strObscured = '';
        while(true)
        {
            $strChar = stream_get_contents(STDIN, 1);
            $intCount = 0;
            $arrRead = array(STDIN);
            $arrWrite = NULL;
            $arrExcept = NULL;
            while (stream_select($arrRead, $arrWrite, $arrExcept, 0, 0) && in_array(STDIN, $arrRead))        
            {
                stream_get_contents(STDIN, 1);
                $intCount++;
            }
            if($strChar === chr(10))
            {
                break;
            }
            if ($intCount === 0)
            {
                if(ord($strChar) === 127)
                {
                    if(strlen($strObscured) > 0)
                    {
                        $strObscured = substr($strObscured, 0, strlen($strObscured) - 1);
                    }
                }
                elseif ($strChar >= ' ')
                {
                    $strObscured .= $strChar;
                }
            }
        }
        readline_callback_handler_remove();
        return $strObscured;
    }
}
