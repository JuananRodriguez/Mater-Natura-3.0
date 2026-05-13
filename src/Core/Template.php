<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class Template
{
    private Security $security;
    private array $jsData = [];
    private array $meta = [
        'title' => MATER_SITE_NAME,
        'description' => MATER_SITE_DESCRIPTION,
        'ogType' => 'website',
        'ogImage' => null,
    ];
    private string $layout = 'dark';
    private string $templatesDir;

    public function __construct(Security $security, ?string $templatesDir = null)
    {
        $this->security = $security;
        $this->templatesDir = $templatesDir ?? MATER_TEMPLATES_DIR;
    }

    public function render(string $view, array $data = [], string $layout = 'dark'): string
    {
        $this->layout = $layout;

        // Extraer datos para la vista
        $meta = $this->meta;
        $jsDataScript = $this->getJsDataScript();
        $csrfField = $this->security->getCsrfField();
        $escape = fn($v) => $this->security->escapeHtml((string) $v);

        // Capturar contenido de la vista
        $viewFile = $this->templatesDir . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        ob_start();
        extract($data);
        require $viewFile;
        $content = ob_get_clean();

        // Renderizar layout
        $layoutFile = $this->templatesDir . '/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            // Fallback a dark si no existe el layout
            $layoutFile = $this->templatesDir . '/layouts/dark.php';
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    public function renderPartial(string $partial, array $data = []): string
    {
        $partialFile = $this->templatesDir . '/partials/' . $partial . '.php';

        if (!file_exists($partialFile)) {
            return '';
        }

        ob_start();
        $escape = fn($v) => $this->security->escapeHtml((string) $v);
        extract($data);
        require $partialFile;
        return ob_get_clean();
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function exposeToJs(string $key, mixed $value): void
    {
        $this->jsData[$key] = $value;
    }

    public function getJsDataScript(): string
    {
        if (empty($this->jsData)) {
            return '';
        }

        $json = json_encode($this->jsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        return '<script>window.__MATER_DATA__=' . $json . ';</script>';
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function csrfField(): string
    {
        return $this->security->getCsrfField();
    }

    public function escapeHtml(string $value): string
    {
        return $this->security->escapeHtml($value);
    }

    // ─── SEO ───

    public function setMetaTitle(string $title): void
    {
        $this->meta['title'] = $title;
    }

    public function setMetaDescription(string $description): void
    {
        $this->meta['description'] = $description;
    }

    public function setOgImage(string $url): void
    {
        $this->meta['ogImage'] = $url;
    }

    public function setOgType(string $type): void
    {
        $this->meta['ogType'] = $type;
    }

    public function renderMetaTags(): string
    {
        $title = $this->security->escapeHtml($this->meta['title']);
        $desc = $this->security->escapeHtml($this->meta['description']);

        $tags = '<title>' . $title . '</title>' . "\n";
        $tags .= '<meta name="description" content="' . $desc . '">' . "\n";
        $tags .= '<meta property="og:title" content="' . $title . '">' . "\n";
        $tags .= '<meta property="og:description" content="' . $desc . '">' . "\n";
        $tags .= '<meta property="og:type" content="' . $this->security->escapeHtml($this->meta['ogType']) . '">' . "\n";

        if ($this->meta['ogImage']) {
            $tags .= '<meta property="og:image" content="' . $this->security->escapeHtml($this->meta['ogImage']) . '">' . "\n";
        }

        $tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";

        return $tags;
    }
}
