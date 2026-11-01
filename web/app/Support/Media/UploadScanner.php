<?php

declare(strict_types=1);

namespace App\Support\Media;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Optional malware scan for uploads (docs/MEDIA_STORAGE.md, section 4).
 *
 * The repository does not ship an engine; when MEDIA_SCAN_COMMAND is set the
 * file is passed to that command (for example clamdscan) before anything is
 * written. A failed scan fails closed by default, because an unscanned file is
 * worse than a rejected upload.
 */
final class UploadScanner
{
    /**
     * @param  (Closure(string): array{exit: int, output: string})|null  $runner
     */
    public function __construct(
        private readonly ?string $command = null,
        private readonly bool $failClosed = true,
        private readonly ?Closure $runner = null,
    ) {}

    public function enabled(): bool
    {
        return is_string($this->command) && trim($this->command) !== '';
    }

    /**
     * @throws ValidationException
     */
    public function scan(string $path): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            $result = $this->run(trim((string) $this->command).' '.escapeshellarg($path), $path);
        } catch (Throwable $exception) {
            Log::warning('media.scan.error', ['message' => $exception->getMessage()]);

            if ($this->failClosed) {
                throw ValidationException::withMessages([
                    'photos' => 'Berkas tidak dapat diverifikasi oleh pemindai malware.',
                ]);
            }

            return;
        }

        $output = strtoupper((string) ($result['output'] ?? ''));
        $exit = (int) ($result['exit'] ?? 0);

        if (str_contains($output, 'FOUND')) {
            Log::warning('media.scan.rejected', ['exit_code' => $exit]);

            throw ValidationException::withMessages([
                'photos' => 'Berkas ditolak oleh pemindai malware.',
            ]);
        }

        if ($exit !== 0) {
            Log::warning('media.scan.error', ['exit_code' => $exit]);

            if ($this->failClosed) {
                throw ValidationException::withMessages([
                    'photos' => 'Berkas tidak dapat diverifikasi oleh pemindai malware.',
                ]);
            }
        }
    }

    /**
     * @return array{exit: int, output: string}
     */
    private function run(string $command, string $path): array
    {
        if ($this->runner !== null) {
            return ($this->runner)($command);
        }

        $result = Process::timeout(30)->run($command);

        return [
            'exit' => (int) $result->exitCode(),
            'output' => $result->output().$result->errorOutput(),
        ];
    }
}
