<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class NewsroomArticleMediaService
{
    public function __construct(
        private readonly NewsroomMediaStorage $storage,
    ) {}

    /**
     * Store one immutable newsroom source image and return verified metadata.
     *
     * @return array{
     *     path:string,
     *     mime_type:string,
     *     bytes:int,
     *     width:int,
     *     height:int,
     *     public_url:string
     * }
     */
    public function store(UploadedFile $file): array
    {
        $mimeType = (string) $file->getMimeType();
        $bytes = (int) $file->getSize();
        $prepared = $this->storage->prepareImageUpload($mimeType, $bytes);
        $path = $prepared['path'];
        $filesystem = Storage::disk($prepared['disk']);

        $contents = $file->getContent();

        if ($contents === false || $contents === '') {
            throw new RuntimeException('Newsroom image upload is empty or unreadable.');
        }

        try {
            $stored = $filesystem->put($path, $contents, [
                'visibility' => 'public',
            ]);

            if ($stored !== true) {
                throw new RuntimeException('Newsroom image could not be stored.');
            }

            $verified = $this->storage->inspectStoredImage(
                $path,
                $prepared['mime_type'],
                $prepared['bytes'],
            );

            return [
                'path' => $verified['path'],
                'mime_type' => $verified['mime_type'],
                'bytes' => $verified['bytes'],
                'width' => $verified['width'],
                'height' => $verified['height'],
                'public_url' => $this->storage->publicUrl($verified['path']),
            ];
        } catch (Throwable $exception) {
            if ($filesystem->exists($path)) {
                $filesystem->delete($path);
            }

            throw $exception;
        }
    }
}
