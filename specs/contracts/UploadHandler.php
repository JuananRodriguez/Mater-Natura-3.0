<?php

namespace MaterNatura\Contracts;

interface UploadHandler {
    public function upload(array $file, string $slug): UploadResult;
    public function serve(string $relativePath): void;
    public function delete(string $relativePath): bool;
    public function getImageUrl(string $relativePath): string;
}
