<?php

declare(strict_types=1);

namespace Helpers;

use Core\Request;

final class LinkTargeting
{
    public static function browserLanguage(Request $request): string
    {
        return strtolower(substr($request->serverString('http_accept_language'), 0, 2));
    }

    public static function overlayId($type): ?string
    {
        $type = is_scalar($type) ? (string) $type : '';

        if (!preg_match('~overlay-(.*)~', $type)) {
            return null;
        }

        return str_replace('overlay-', '', $type);
    }
}
