<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class Template
{
    private array $jsData = [];
    private array $meta = [
        'title' => MATER_SITE_NAME,
        'description' => MATER_SITE_DESCRIPTION,
        'ogType' => 'website',
        'ogImage' => null,
    ];
    private string $layout = 'dark';
    private ?PluginManager $pluginManager = null;
    private ?string $canonicalUrl = null;
    private string $robots = 'index, follow';
    private array $jsonLd = [];
    private array $alternateLinks = [];
    private array $breadcrumbs = [];

    public function __construct(
        private Security $security,
        private ?string $templatesDir = null,
    ) {
        $this->templatesDir = $templatesDir ?? MATER_TEMPLATES_DIR;
    }

    public function setPluginManager(?PluginManager $pm): void
    {
        $this->pluginManager = $pm;
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

        // Hacer accesible PluginManager a los layouts (para hooks como page.footer)
        $pluginManager = $this->pluginManager;

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

    public function setCanonicalUrl(?string $url): void
    {
        $this->canonicalUrl = $url;
    }

    public function setRobots(string $robots): void
    {
        $this->robots = $robots;
    }

    public function addJsonLd(array $data): void
    {
        $this->jsonLd[] = $data;
    }

    public function addAlternateLink(string $rel, string $href): void
    {
        $this->alternateLinks[$rel] = $href;
    }

    public function renderAlternateLinks(): string
    {
        if (empty($this->alternateLinks)) {
            return '';
        }
        $tags = '';
        foreach ($this->alternateLinks as $rel => $href) {
            $tags .= '<link rel="' . $this->security->escapeHtml($rel) . '" href="' . $this->security->escapeHtml($href) . '">' . "\n";
        }
        return $tags;
    }

    public function renderJsonLd(): string
    {
        if (empty($this->jsonLd)) {
            return '';
        }

        $blocks = [];
        foreach ($this->jsonLd as $data) {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $blocks[] = '<script type="application/ld+json">' . $json . '</script>';
        }
        return implode("\n", $blocks);
    }

    // ─── Breadcrumbs ───

    public function setBreadcrumbs(array $crumbs): void
    {
        $this->breadcrumbs = $crumbs;
    }

    public function addBreadcrumb(string $label, ?string $url = null): void
    {
        $this->breadcrumbs[] = ['label' => $label, 'url' => $url];
    }

    public function renderBreadcrumbs(): string
    {
        if (empty($this->breadcrumbs)) {
            return '';
        }

        $items = count($this->breadcrumbs);
        $html = '<nav aria-label="Breadcrumb" style="margin-bottom:12px;min-height:20px;line-height:20px;overflow:hidden">' . "\n"
              . '  <ol style="list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap">' . "\n";

        foreach ($this->breadcrumbs as $i => $crumb) {
            $isLast = $i === $items - 1;
            $label = $this->security->escapeHtml($crumb['label']);

            if ($i > 0) {
                $html .= '    <li style="margin:0 4px;color:#888">/</li>' . "\n";
            }

            if ($isLast || empty($crumb['url'])) {
                $html .= '    <li' . ($isLast ? ' aria-current="page"' : '') . '>'
                      . '<span style="color:' . ($isLast ? 'inherit;font-weight:600' : '#888') . '">'
                      . $label . '</span></li>' . "\n";
            } else {
                $html .= '    <li><a href="' . $this->security->escapeHtml($crumb['url']) . '" style="color:#555;text-decoration:underline">'
                      . $label . '</a></li>' . "\n";
            }
        }

        $html .= '  </ol>' . "\n"
              . '</nav>' . "\n";

        return $html;
    }

    public function renderMetaTags(): string
    {
        $title = $this->security->escapeHtml($this->meta['title']);
        $desc = $this->security->escapeHtml($this->meta['description']);

        $tags = '<title>' . $title . '</title>' . "\n";
        $tags .= '<meta name="description" content="' . $desc . '">' . "\n";

        // Robots
        $tags .= '<meta name="robots" content="' . $this->security->escapeHtml($this->robots) . '">' . "\n";

        // Canonical
        if ($this->canonicalUrl) {
            $tags .= '<link rel="canonical" href="' . $this->security->escapeHtml($this->canonicalUrl) . '">' . "\n";
        }

        // Open Graph
        $tags .= '<meta property="og:title" content="' . $title . '">' . "\n";
        $tags .= '<meta property="og:description" content="' . $desc . '">' . "\n";
        $tags .= '<meta property="og:type" content="' . $this->security->escapeHtml($this->meta['ogType']) . '">' . "\n";
        $tags .= '<meta property="og:url" content="' . ($this->canonicalUrl ? $this->security->escapeHtml($this->canonicalUrl) : '') . '">' . "\n";

        if ($this->meta['ogImage']) {
            $tags .= '<meta property="og:image" content="' . $this->security->escapeHtml($this->meta['ogImage']) . '">' . "\n";
        }

        // Twitter
        $tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";

        // JSON-LD
        $tags .= $this->renderJsonLd();

        // Alternate links (prev/next, etc.)
        $tags .= $this->renderAlternateLinks();

        return $tags;
    }
}
