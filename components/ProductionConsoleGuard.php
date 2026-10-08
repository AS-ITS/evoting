<?php

namespace app\components;

use app\models\Logs;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;

/**
 * 正式環境 Console 危險命令防護
 */
class ProductionConsoleGuard implements BootstrapInterface
{
    /** @var array<int, array{commands: string[], actions: string[], override: string, message: string}> */
    private const GUARDED_COMMANDS = [
        [
            'commands' => ['migrate-passwords'],
            'actions' => ['reencrypt', 'upgrade-crypto'],
            'override' => 'ALLOW_PRODUCTION_MIGRATE',
            'message' => '若確有必要請設定 ALLOW_PRODUCTION_MIGRATE=1。',
        ],
        [
            'commands' => ['migrate'],
            'actions' => ['up', 'down', 'fresh', 'redo'],
            'override' => 'ALLOW_PRODUCTION_MIGRATE',
            'message' => '若確有必要請設定 ALLOW_PRODUCTION_MIGRATE=1。',
        ],
        [
            'commands' => ['db'],
            'actions' => ['import', 'drop', 'restore'],
            'override' => 'ALLOW_PRODUCTION_MIGRATE',
            'message' => '若確有必要請設定 ALLOW_PRODUCTION_MIGRATE=1。',
        ],
        [
            'commands' => ['fixture'],
            'actions' => ['load', 'unload', 'generate'],
            'override' => 'ALLOW_PRODUCTION_FIXTURE',
            'message' => '若為測試庫維護請設定 ALLOW_PRODUCTION_FIXTURE=1 並使用 --appconfig=config/console-test.php。',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function bootstrap($app): void
    {
        if (!$app instanceof \yii\console\Application) {
            return;
        }

        $env = strtolower(getenv('APP_ENV') ?: '');
        if (!in_array($env, ['production', 'prod', 'product'], true)) {
            return;
        }

        $argv = $_SERVER['argv'] ?? [];
        $command = $argv[1] ?? '';
        $action = $argv[2] ?? '';

        $evaluation = self::evaluateCommand($command, $action);
        if ($evaluation === null) {
            return;
        }

        if (!$evaluation['allowed']) {
            self::audit(Logs::SYSTEM_CONSOLE_BLOCKED, $evaluation);
            fwrite(
                STDERR,
                "[安全] 正式環境已禁止 {$evaluation['route']}。{$evaluation['message']}\n"
            );
            exit(1);
        }

        self::audit(Logs::SYSTEM_CONSOLE_OVERRIDE, $evaluation);
    }

    /**
     * @return array{route: string, command: string, action: string, overrideEnv: string, message: string, allowed: bool}|null
     */
    public static function evaluateCommand(string $command, string $action): ?array
    {
        foreach (self::GUARDED_COMMANDS as $rule) {
            if (!in_array($command, $rule['commands'], true)) {
                continue;
            }
            if (!in_array($action, $rule['actions'], true)) {
                continue;
            }

            $overrideEnv = $rule['override'];
            return [
                'route' => "{$command}/{$action}",
                'command' => $command,
                'action' => $action,
                'overrideEnv' => $overrideEnv,
                'message' => $rule['message'],
                'allowed' => getenv($overrideEnv) === '1',
            ];
        }

        return null;
    }

    /**
     * @param array{route: string, command: string, action: string, overrideEnv: string, message: string, allowed: bool} $evaluation
     */
    private static function audit(string $type, array $evaluation): void
    {
        try {
            if (!Yii::$app->has('db')) {
                return;
            }

            Logs::addConsole($type, [
                'route' => $evaluation['route'],
                'outcome' => $type === Logs::SYSTEM_CONSOLE_BLOCKED ? 'blocked' : 'allowed',
                'overrideEnv' => $evaluation['overrideEnv'],
                'hostname' => gethostname() ?: null,
            ]);
        } catch (\Throwable $e) {
            Yii::warning('ProductionConsoleGuard audit failed: ' . $e->getMessage(), __METHOD__);
        }
    }
}
