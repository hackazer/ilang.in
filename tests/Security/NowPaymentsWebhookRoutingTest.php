<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Request;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/app/traits/Links.php';
require_once dirname(__DIR__, 2).'/app/traits/Payments.php';
require_once dirname(__DIR__, 2).'/app/controllers/WebhookController.php';

final class NowPaymentsWebhookRoutingTest extends TestCase
{
    public function testDedicatedControllerActionDispatchesProviderExactlyOnce(): void
    {
        $controller = new RoutingWebhookController();
        $request = new Request();

        self::assertSame('handled', $controller->nowpayments($request));
        self::assertSame([['nowpayments', 'webhook']], $controller->processorCalls);
        self::assertSame(1, $controller->handlerCalls);
    }
}

final class RoutingWebhookController extends \Webhook
{
    /** @var list<array{0:string,1:string}> */
    public array $processorCalls = [];
    public int $handlerCalls = 0;

    protected function registerPaymentSuccessHook(): void
    {
    }

    public function processor($type = null, $action = null): callable
    {
        $this->processorCalls[] = [(string) $type, (string) $action];

        return function (Request $request): string {
            $this->handlerCalls++;

            return 'handled';
        };
    }
}
