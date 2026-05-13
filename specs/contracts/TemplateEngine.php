<?php

namespace MaterNatura\Contracts;

interface TemplateEngine {
    public function render(string $view, array $data = [], string $layout = 'dark'): string;
    public function renderPartial(string $partial, array $data = []): string;
    public function setLayout(string $layout): void;
    public function exposeToJs(string $key, mixed $value): void;
    public function getJsDataScript(): string;
    public function setMetaTitle(string $title): void;
    public function setMetaDescription(string $description): void;
    public function setOgImage(string $url): void;
    public function setOgType(string $type): void;
    public function renderMetaTags(): string;
}
