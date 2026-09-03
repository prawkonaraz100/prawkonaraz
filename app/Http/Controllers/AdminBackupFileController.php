<?php

namespace App\Http\Controllers;

use App\Support\DatabaseBackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBackupFileController extends Controller
{
    public function download(Request $request, DatabaseBackupService $backupService): StreamedResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        $reference = (string) $request->query('reference', '');
        abort_if($reference === '', 404);

        $payload = $backupService->downloadPayload($reference);
        $stream = $payload['stream'];
        $headers = array_filter([
            'Content-Type' => $payload['content_type'] ?: 'application/gzip',
            'Content-Length' => $payload['content_length'],
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $payload['filename'], $headers);
    }
}
