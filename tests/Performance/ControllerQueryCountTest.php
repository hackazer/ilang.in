<?php

declare(strict_types=1);

namespace Tests\Performance;

use Admin\Dashboard as AdminDashboard;
use Admin\Users as AdminUsers;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use User\Dashboard as UserDashboard;

require_once dirname(__DIR__, 2).'/app/controllers/user/DashboardController.php';
require_once dirname(__DIR__, 2).'/app/controllers/admin/DashboardController.php';
require_once dirname(__DIR__, 2).'/app/controllers/admin/UsersController.php';

final class ControllerQueryCountTest extends TestCase
{
    public function testPricingPerformsOneTrialPaymentLookup(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/controllers/SubscriptionController.php');

        self::assertIsString($source);

        $pricingStart = strpos($source, 'public function pricing()');
        $checkoutStart = strpos($source, 'public function checkout(');

        self::assertNotFalse($pricingStart);
        self::assertNotFalse($checkoutStart);

        $pricing = substr($source, $pricingStart, $checkoutStart - $pricingStart);
        $trialLookup = strpos($pricing, 'DB::payment()');
        $firstPlanLoop = strpos($pricing, 'foreach(');

        self::assertSame(1, substr_count($pricing, 'DB::payment()'));
        self::assertNotFalse($trialLookup);
        self::assertNotFalse($firstPlanLoop);
        self::assertLessThan($firstPlanLoop, $trialLookup);
    }

    public function testUserLinkBundlesAreLoadedInOneQuery(): void
    {
        $queryCount = 0;
        $requestedIds = [];
        $urls = [
            (object) ['id' => 1, 'bundle' => 7],
            (object) ['id' => 2, 'bundle' => 7],
            (object) ['id' => 3, 'bundle' => 8],
            (object) ['id' => 4, 'bundle' => null],
        ];

        $result = $this->invokePrivateStatic(
            UserDashboard::class,
            'withBundleNames',
            [
                $urls,
                static function (array $ids) use (&$queryCount, &$requestedIds): array {
                    $queryCount++;
                    $requestedIds = $ids;

                    return [
                        (object) ['id' => 7, 'name' => 'Alpha'],
                        (object) ['id' => 8, 'name' => 'Beta'],
                    ];
                },
            ]
        );

        self::assertSame(1, $queryCount);
        self::assertSame([7, 8], $requestedIds);
        self::assertSame('Alpha', $result[0]->bundlename);
        self::assertSame('Alpha', $result[1]->bundlename);
        self::assertSame('Beta', $result[2]->bundlename);
        self::assertObjectNotHasProperty('bundlename', $result[3]);
    }

    public function testRecentActivityRelationsUseAtMostThreeBatchQueries(): void
    {
        $queryCount = 0;
        $requested = [];
        $activity = [
            (object) ['urlid' => 10],
            (object) ['urlid' => 11],
            (object) ['urlid' => 10],
            (object) ['urlid' => 999],
        ];

        $result = $this->invokePrivateStatic(
            UserDashboard::class,
            'withActivityRelations',
            [
                $activity,
                static function (array $ids) use (&$queryCount, &$requested): array {
                    $queryCount++;
                    $requested['urls'] = $ids;

                    return [
                        (object) ['id' => 10, 'qrid' => 1, 'profileid' => null],
                        (object) ['id' => 11, 'qrid' => null, 'profileid' => 5],
                    ];
                },
                static function (array $ids) use (&$queryCount, &$requested): array {
                    $queryCount++;
                    $requested['qrs'] = $ids;

                    return [(object) ['urlid' => 10, 'name' => 'QR Link']];
                },
                static function (array $ids) use (&$queryCount, &$requested): array {
                    $queryCount++;
                    $requested['profiles'] = $ids;

                    return [(object) ['urlid' => 11, 'name' => 'Bio Link']];
                },
            ]
        );

        self::assertSame(3, $queryCount);
        self::assertSame([
            'urls' => [10, 11, 999],
            'qrs' => [10],
            'profiles' => [11],
        ], $requested);
        self::assertSame([0, 1, 2], array_keys($result));
        self::assertSame('QR Link', $result[0]->qr);
        self::assertSame('Bio Link', $result[1]->profile);
        self::assertSame($result[0]->url, $result[2]->url);
    }

    public function testAdminDashboardSubscriptionsUseTwoBatchQueries(): void
    {
        $queryCount = 0;
        $requested = [];
        $subscriptions = [
            (object) ['userid' => 1, 'planid' => 5],
            (object) ['userid' => 1, 'planid' => 5],
            (object) ['userid' => 2, 'planid' => 6],
        ];
        $userOne = new class {
            public int $id = 1;
            public string $email = 'one@example.com';

            public function avatar(): string
            {
                return 'avatar-one';
            }
        };
        $userTwo = new class {
            public int $id = 2;
            public string $email = 'two@example.com';

            public function avatar(): string
            {
                return 'avatar-two';
            }
        };

        $result = $this->invokePrivateStatic(
            AdminDashboard::class,
            'withSubscriptionDetails',
            [
                $subscriptions,
                false,
                static function (array $ids) use (&$queryCount, &$requested, $userOne, $userTwo): array {
                    $queryCount++;
                    $requested['users'] = $ids;

                    return [$userOne, $userTwo];
                },
                static function (array $ids) use (&$queryCount, &$requested): array {
                    $queryCount++;
                    $requested['plans'] = $ids;

                    return [
                        (object) ['id' => 5, 'name' => 'Starter'],
                        (object) ['id' => 6, 'name' => 'Business'],
                    ];
                },
            ]
        );

        self::assertSame(2, $queryCount);
        self::assertSame(['users' => [1, 2], 'plans' => [5, 6]], $requested);
        self::assertSame('one@example.com', $result[0]->user);
        self::assertSame('avatar-one', $result[1]->useravatar);
        self::assertSame('Business', $result[2]->plan);
    }

    public function testAdminUserIndexDetailsUseTwoBatchQueries(): void
    {
        $queryCount = 0;
        $requested = [];
        $users = [
            (object) ['id' => 1, 'pro' => 1, 'planid' => 5],
            (object) ['id' => 2, 'pro' => 1, 'planid' => 5],
            (object) ['id' => 3, 'pro' => 1, 'planid' => 6],
        ];

        $result = $this->invokePrivateStatic(
            AdminUsers::class,
            'withListDetails',
            [
                $users,
                true,
                'Pro',
                static function (array $ids) use (&$queryCount, &$requested): array {
                    $queryCount++;
                    $requested['counts'] = $ids;

                    return [
                        (object) ['userid' => 1, 'count' => 4],
                        (object) ['userid' => 3, 'count' => 9],
                    ];
                },
                static function (array $ids) use (&$queryCount, &$requested): array {
                    $queryCount++;
                    $requested['plans'] = $ids;

                    return [(object) ['id' => 5, 'name' => 'Starter']];
                },
            ]
        );

        self::assertSame(2, $queryCount);
        self::assertSame(['counts' => [1, 2, 3], 'plans' => [5, 6]], $requested);
        self::assertSame(4, $result[0]->count);
        self::assertSame(0, $result[1]->count);
        self::assertSame(9, $result[2]->count);
        self::assertSame('Starter', $result[0]->pro);
        self::assertSame('Starter', $result[1]->pro);
        self::assertSame('Pro', $result[2]->pro);
    }

    public function testFilteredAdminUserListsUseOneCountQuery(): void
    {
        $queryCount = 0;
        $users = [
            (object) ['id' => 1, 'pro' => 1, 'planid' => 5],
            (object) ['id' => 2, 'pro' => 0, 'planid' => null],
        ];

        $result = $this->invokePrivateStatic(
            AdminUsers::class,
            'withListDetails',
            [
                $users,
                false,
                'Pro',
                static function (array $ids) use (&$queryCount): array {
                    $queryCount++;
                    self::assertSame([1, 2], $ids);

                    return [(object) ['userid' => 2, 'count' => 3]];
                },
                static function () use (&$queryCount): array {
                    $queryCount++;

                    return [];
                },
            ]
        );

        self::assertSame(1, $queryCount);
        self::assertSame(0, $result[0]->count);
        self::assertSame(3, $result[1]->count);
        self::assertSame(1, $result[0]->pro);
    }

    private function invokePrivateStatic(string $class, string $method, array $arguments): mixed
    {
        if (!method_exists($class, $method)) {
            self::fail($class.'::'.$method.' is missing.');
        }

        $reflection = new ReflectionMethod($class, $method);

        return $reflection->invoke(null, ...$arguments);
    }
}
