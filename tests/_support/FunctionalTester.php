<?php


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
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

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
}
