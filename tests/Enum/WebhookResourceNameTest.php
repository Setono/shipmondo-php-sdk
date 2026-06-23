<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Enum;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebhookResourceNameTest extends TestCase
{
    #[Test]
    public function it_lists_the_actions_for_each_resource(): void
    {
        self::assertSame(
            [WebhookAction::Create, WebhookAction::Cancel],
            WebhookResourceName::Shipments->actions(),
        );
        self::assertSame(
            [WebhookAction::Latest, WebhookAction::Delivered],
            WebhookResourceName::ShipmentMonitor->actions(),
        );
        self::assertContains(WebhookAction::Delete, WebhookResourceName::Orders->actions());
    }

    #[Test]
    #[DataProvider('combinations')]
    public function it_reports_whether_an_action_is_supported(WebhookResourceName $resource, WebhookAction $action, bool $supported): void
    {
        self::assertSame($supported, $resource->supports($action));
    }

    /**
     * @return \Generator<string, array{WebhookResourceName, WebhookAction, bool}>
     */
    public static function combinations(): \Generator
    {
        yield 'shipments + cancel' => [WebhookResourceName::Shipments, WebhookAction::Cancel, true];
        yield 'shipments + delete' => [WebhookResourceName::Shipments, WebhookAction::Delete, false];
        yield 'orders + payment_captured' => [WebhookResourceName::Orders, WebhookAction::PaymentCaptured, true];
        yield 'shipment monitor + delivered' => [WebhookResourceName::ShipmentMonitor, WebhookAction::Delivered, true];
        yield 'shipment monitor + create' => [WebhookResourceName::ShipmentMonitor, WebhookAction::Create, false];
    }

    #[Test]
    public function every_action_belongs_to_at_least_one_resource(): void
    {
        $mapped = [];
        foreach (WebhookResourceName::cases() as $resource) {
            foreach ($resource->actions() as $action) {
                $mapped[$action->value] = true;
            }
        }

        // Guards against forgetting to map a newly-added WebhookAction case.
        foreach (WebhookAction::cases() as $action) {
            self::assertArrayHasKey(
                $action->value,
                $mapped,
                sprintf('Action "%s" is not mapped to any resource.', $action->value),
            );
        }
    }
}
