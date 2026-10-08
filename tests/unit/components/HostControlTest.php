<?php

namespace app\tests\unit\components;

use Yii;
use yii\filters\HostControl;

/**
 * HostControl 行為測試（P3-1）
 *
 * 對應 config/web.php：ALLOWED_HOSTS 未設定時 allowedHosts 為 []（拒絕所有）；
 * 若要跳過檢查需設 allowedHosts = null。
 */
class HostControlTest extends \Codeception\Test\Unit
{
    protected function _after()
    {
        unset($_SERVER['HTTP_HOST']);
    }

    public function testNullAllowedHostsSkipsCheck()
    {
        $filter = new HostControl(['allowedHosts' => null]);
        $this->assertTrue($filter->beforeAction($this->createAction()));
    }

    public function testAllowedHostPasses()
    {
        $_SERVER['HTTP_HOST'] = 'allowed.example.com';
        Yii::$app->request->headers->set('Host', 'allowed.example.com');

        $filter = new HostControl(['allowedHosts' => ['allowed.example.com']]);
        $this->assertTrue($filter->beforeAction($this->createAction()));
    }

    public function testDisallowedHostIsRejected()
    {
        $_SERVER['HTTP_HOST'] = 'evil.example.com';
        Yii::$app->request->headers->set('Host', 'evil.example.com');

        $denied = false;
        $filter = new HostControl([
            'allowedHosts' => ['allowed.example.com'],
            'denyCallback' => function () use (&$denied) {
                $denied = true;
            },
        ]);

        $this->assertFalse($filter->beforeAction($this->createAction()));
        $this->assertTrue($denied, 'Disallowed host should invoke denyCallback');
    }

    public function testWildcardAllowedHostPasses()
    {
        $_SERVER['HTTP_HOST'] = 'sub.example.com';
        Yii::$app->request->headers->set('Host', 'sub.example.com');

        $filter = new HostControl(['allowedHosts' => ['*.example.com']]);
        $this->assertTrue($filter->beforeAction($this->createAction()));
    }

    private function createAction()
    {
        $controller = new class('test', Yii::$app) extends \yii\web\Controller {
            public function actionIndex()
            {
            }
        };

        return new \yii\base\InlineAction('index', $controller, 'actionIndex');
    }
}
