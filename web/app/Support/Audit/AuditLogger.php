<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Records sensitive actions. Callers must only pass already-sanitised metadata:
 * never tokens, passwords, signed URLs, or unnecessary personal data
 * (docs/SECURITY.md, section 8).
 */
final class AuditLogger
{
    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function log(
        string $action,
        string $resourceType,
        ?string $resourceId,
        ?User $actor,
        array $metadata = [],
        string $result = 'success',
    ): AuditLog {
        return AuditLog::create([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'result' => $result,
            'request_id' => $this->requestId(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private function requestId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app(Request::class);
        $requestId = $request->headers->get('X-Request-Id');

        return is_string($requestId) ? substr($requestId, 0, 64) : null;
    }
}
