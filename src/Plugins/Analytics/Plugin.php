<?php

declare(strict_types=1);

namespace MaterNatura\Plugins\Analytics;

use MaterNatura\Core\Auth;
use MaterNatura\Core\Database;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;
use MaterNatura\Plugins\PluginInterface;

class Plugin implements PluginInterface
{
    private ?Database $db = null;
    private ?Security $security = null;
    private ?Auth $auth = null;

    private const VERSION = '1.0.0';
    private const PLUGIN_DIR = __DIR__;

    public function setDatabase(Database $db): void
    {
        $this->db = $db;
    }

    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    public function setAuth(Auth $auth): void
    {
        $this->auth = $auth;
    }

    public function getMeta(): array
    {
        return [
            'name'        => 'Analytics',
            'version'     => self::VERSION,
            'description' => 'Analítica de visitas: páginas vistas, tiempo en página, origen y destino del tráfico.',
            'slug'        => 'analytics',
            'author'      => 'Mater-Natura',
            'requires'    => '1.0.0',
        ];
    }

    public function registerHooks(): array
    {
        return [
            [
                'hook' => 'admin.menu.add',
                'priority'  => 10,
            ],
            [
                'hook' => 'admin.dashboard.widgets',
                'priority'  => 10,
            ],
            [
                'hook' => 'page.viewed',
                'priority'  => 10,
            ],
            [
                'hook' => 'page.footer',
                'priority'  => 10,
            ],
            [
                'hook' => 'analytics.track',
                'priority'  => 10,
            ],
        ];
    }

    // ─── Activación / Desactivación ───

    public function onActivate(): void
    {
        if ($this->db === null) {
            return;
        }

        $migrationFile = self::PLUGIN_DIR . '/migrations/001_analytics.sql';
        if (!file_exists($migrationFile)) {
            error_log('[Analytics] Migration file not found: ' . $migrationFile);
            return;
        }

        $sql = file_get_contents($migrationFile);
        if ($sql === false || trim($sql) === '') {
            return;
        }

        try {
            $this->db->getPdo()->exec($sql);
            error_log('[Analytics] Migración ejecutada correctamente');
        } catch (\Throwable $e) {
            error_log('[Analytics] Error en migración: ' . $e->getMessage());
        }
    }

    public function onDeactivate(): void
    {
        // No eliminamos datos al desactivar — se conservan para cuando se reactive
        error_log('[Analytics] Plugin desactivado — los datos se conservan');
    }

    // ─── Hooks ───

    /**
     * Hook: admin.menu.add → Añade enlace en el sidebar del admin
     */
    public function onAdminMenuAdd(array $context): ?array
    {
        return [
            'label' => 'Analytics',
            'url'   => '/admin/analytics',
            'icon'  => 'globe-alt',
            'order' => 20,
        ];
    }

    /**
     * Hook: admin.dashboard.widgets → Widget resumen en el dashboard
     */
    public function onAdminDashboardWidgets(array $context): ?array
    {
        if ($this->db === null) {
            return null;
        }

        try {
            $today = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM analytics_pageviews WHERE DATE(visited_at) = CURDATE()"
            );
            $week = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            );
            $month = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            );
        } catch (\Throwable $e) {
            return null;
        }

        $todayCnt = (int) ($today['cnt'] ?? 0);
        $weekCnt  = (int) ($week['cnt'] ?? 0);
        $monthCnt = (int) ($month['cnt'] ?? 0);

        return [
            'title' => '📊 Analytics',
            'content' => '<div class="analytics-stats">
                <span class="stat"><strong>' . $todayCnt . '</strong> hoy</span>
                <span class="stat"><strong>' . $weekCnt . '</strong> 7 días</span>
                <span class="stat"><strong>' . $monthCnt . '</strong> 30 días</span>
            </div>
            <a href="/admin/analytics" class="btn btn-sm">Ver detalles</a>',
        ];
    }

    /**
     * Hook: page.viewed → Server-side tracking básico (IP, URL, referer)
     * Se ejecuta desde App.php en cada petición.
     */
    public function onPageViewed(array $context): void
    {
        if ($this->db === null) {
            return;
        }

        // No trackear peticiones a assets, admin, ni endpoints internos
        $url = $context['page_url'] ?? '/';
        if (preg_match('#^/(assets/|media/|admin(/|$)|analytics/track|login|logout|favicon\\.ico|robots\\.txt)#', $url)) {
            return;
        }

        // Solo GET requests (no POST submissions)
        $method = $context['method'] ?? 'GET';
        if ($method !== 'GET') {
            return;
        }

        $ipRaw = $context['ip'] ?? '127.0.0.1';
        $ipBin = inet_pton($ipRaw);

        // Determinar referer type
        $referer = $context['referer'] ?? null;
        $refererType = $this->classifyReferer($referer);

        // Generar session_id server-side (para tracking sin JS)
        $sessionId = $_COOKIE['MN_ANALYTICS_SESSION'] ?? null;
        if (!$sessionId) {
            $sessionId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            setcookie('MN_ANALYTICS_SESSION', $sessionId, [
                'expires' => time() + 86400 * 30,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        try {
            $this->db->insert('analytics_pageviews', [
                'session_id'     => $sessionId,
                'visitor_ip'     => $ipBin,
                'user_agent'     => $context['user_agent'] ?? null,
                'page_url'       => $url,
                'page_title'     => null, // Se rellena client-side si JS está activo
                'referer_url'    => $referer,
                'referer_type'   => $refererType,
                'query_string'   => $context['query_string'] ?? null,
                'time_on_page_seconds' => 0,
                'is_entry'       => 1,
                'is_exit'        => 0,
            ]);
        } catch (\Throwable $e) {
            error_log('[Analytics] Error tracking pageview: ' . $e->getMessage());
        }
    }

    /**
     * Hook: page.footer → Inyecta el script de tracking JS
     */
    public function onPageFooter(array $context): string
    {
        $scriptPath = self::PLUGIN_DIR . '/assets/analytics.js';
        if (!file_exists($scriptPath)) {
            return '';
        }

        $js = file_get_contents($scriptPath);
        if ($js === false || trim($js) === '') {
            return '';
        }

        return '<script>' . $js . '</script>';
    }

    /**
     * Hook: analytics.track → Procesa datos del JS tracking (sendBeacon)
     * Recibe datos via POST JSON desde el cliente.
     */
    public function onAnalyticsTrack(array $data): void
    {
        if ($this->db === null) {
            return;
        }

        $type = $data['type'] ?? '';
        $sessionId = $data['session_id'] ?? '';
        $pageUrl = $data['page_url'] ?? '';

        if (empty($sessionId) || empty($pageUrl)) {
            return;
        }

        // Propagar el session_id del JS a la cookie del servidor
        // Así las siguientes peticiones server-side reutilizan el mismo session_id
        setcookie('MN_ANALYTICS_SESSION', $sessionId, [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        try {
            switch ($type) {
                case 'pageview':
                    // Primero buscar una entrada existente con el mismo session_id (coincidencia si ya hay cookie)
                    $existing = $this->db->fetchOne(
                        "SELECT id, session_id FROM analytics_pageviews
                         WHERE session_id = ? AND page_url = ?
                         ORDER BY visited_at DESC LIMIT 1",
                        [$sessionId, $pageUrl]
                    );

                    // Si no encontró, buscar entrada server-side reciente (misma URL, mismo IP, últimos 30s)
                    // Esto ocurre cuando la cookie server-side aún no tenía el session_id del JS
                    if (!$existing) {
                        $existing = $this->db->fetchOne(
                            "SELECT id, session_id FROM analytics_pageviews
                             WHERE page_url = ? AND page_title IS NULL
                               AND visited_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
                             ORDER BY visited_at DESC LIMIT 1",
                            [$pageUrl]
                        );
                    }

                    if ($existing) {
                        // Actualizar la entrada existente con datos del JS
                        $this->db->update('analytics_pageviews', [
                            'session_id'  => $sessionId,
                            'page_title'  => $data['page_title'] ?? null,
                            'referer_url' => $data['referer'] ?? null,
                        ], 'id = ?', [(int) $existing['id']]);
                    } else {
                        // No hay entrada previa — insertar nueva
                        $ipRaw = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $ipBin = inet_pton($ipRaw);
                        $referer = $data['referer'] ?? '';
                        $this->db->insert('analytics_pageviews', [
                            'session_id'     => $sessionId,
                            'visitor_ip'     => $ipBin,
                            'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
                            'page_url'       => $pageUrl,
                            'page_title'     => $data['page_title'] ?? null,
                            'referer_url'    => $referer ?: null,
                            'referer_type'   => $this->classifyReferer($referer),
                            'time_on_page_seconds' => 0,
                            'is_entry'       => 1,
                            'is_exit'        => 0,
                        ]);
                    }
                    break;

                case 'heartbeat':
                    // Actualizar tiempo en página
                    $timeOnPage = (int) ($data['elapsed'] ?? 0);
                    if ($timeOnPage > 0) {
                        $this->db->getPdo()->prepare(
                            "UPDATE analytics_pageviews
                             SET time_on_page_seconds = GREATEST(time_on_page_seconds, ?)
                             WHERE session_id = ? AND page_url = ?
                             ORDER BY visited_at DESC LIMIT 1"
                        )->execute([$timeOnPage, $sessionId, $pageUrl]);
                    }
                    break;

                case 'exit':
                    // Marcar como salida y registrar destino
                    $exitType = $data['exit_type'] ?? 'closed_tab';
                    $exitUrl = $data['exit_url'] ?? null;

                    $this->db->getPdo()->prepare(
                        "UPDATE analytics_pageviews
                         SET is_exit = 1,
                             exit_url = ?,
                             time_on_page_seconds = GREATEST(time_on_page_seconds, ?)
                         WHERE session_id = ? AND page_url = ?
                         ORDER BY visited_at DESC LIMIT 1"
                    )->execute([$exitUrl, (int) ($data['elapsed'] ?? 0), $sessionId, $pageUrl]);
                    break;

                case 'outbound':
                    // Registrar salida a enlace externo
                    $this->db->getPdo()->prepare(
                        "UPDATE analytics_pageviews
                         SET is_exit = 1,
                             exit_url = ?,
                             time_on_page_seconds = GREATEST(time_on_page_seconds, ?)
                         WHERE session_id = ? AND page_url = ?
                         ORDER BY visited_at DESC LIMIT 1"
                    )->execute([$data['exit_url'] ?? null, (int) ($data['elapsed'] ?? 0), $sessionId, $pageUrl]);
                    break;

                case 'internal_nav':
                    // Marcar que no fue cierre de pestaña, sino navegación interna
                    $this->db->getPdo()->prepare(
                        "UPDATE analytics_pageviews
                         SET is_exit = 1,
                             exit_url = ?,
                             exit_type = 'internal_link',
                             time_on_page_seconds = GREATEST(time_on_page_seconds, ?)
                         WHERE session_id = ? AND page_url = ?
                         ORDER BY visited_at DESC LIMIT 1"
                    )->execute([$data['exit_url'] ?? null, (int) ($data['elapsed'] ?? 0), $sessionId, $pageUrl]);
                    break;
            }
        } catch (\Throwable $e) {
            error_log('[Analytics] Error procesando tracking: ' . $e->getMessage());
        }
    }

    // ─── Admin Dashboard ───

    /**
     * Renderiza la página completa de Analytics (/admin/analytics)
     */
    public function adminAnalyticsDashboard(): string
    {
        if ($this->db === null || $this->security === null) {
            return '<p>Error: Plugin no inicializado correctamente.</p>';
        }

        // Recolectar datos
        $stats = $this->getStats();
        $topPages = $this->getTopPages();
        $recentSessions = $this->getRecentSessions();
        $trafficSources = $this->getTrafficSources();
        $dailyTrend = $this->getDailyTrend(14); // últimos 14 días

        $escape = fn($v) => $this->security->escapeHtml((string) $v);

        ob_start();
        require self::PLUGIN_DIR . '/templates/admin-analytics.php';
        return ob_get_clean();
    }

    // ─── Consultas de datos ───

    private function getStats(): array
    {
        $today = $yesterday = $week = $month = $total = $uniqueToday = $avgTime = $bounceRate = 0;
        $totalSessions = $singlePageSessions = 0;
        try {
            $today     = (int) ($this->db->fetchOne("SELECT COUNT(*) as cnt FROM analytics_pageviews WHERE DATE(visited_at) = CURDATE()")['cnt'] ?? 0);
            $yesterday = (int) ($this->db->fetchOne("SELECT COUNT(*) as cnt FROM analytics_pageviews WHERE DATE(visited_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")['cnt'] ?? 0);
            $week      = (int) ($this->db->fetchOne("SELECT COUNT(*) as cnt FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")['cnt'] ?? 0);
            $month     = (int) ($this->db->fetchOne("SELECT COUNT(*) as cnt FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")['cnt'] ?? 0);
            $total     = (int) ($this->db->fetchOne("SELECT COUNT(*) as cnt FROM analytics_pageviews")['cnt'] ?? 0);

            // Visitantes únicos hoy
            $uniqueToday = (int) ($this->db->fetchOne(
                "SELECT COUNT(DISTINCT session_id) as cnt FROM analytics_pageviews WHERE DATE(visited_at) = CURDATE()"
            )['cnt'] ?? 0);

            // Tiempo medio en página (últimos 7 días, solo páginas con tiempo > 0)
            $avgTime = (int) ($this->db->fetchOne(
                "SELECT COALESCE(AVG(time_on_page_seconds), 0) as avg_time
                 FROM analytics_pageviews
                 WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 AND time_on_page_seconds > 0"
            )['avg_time'] ?? 0);

            // Porcentaje de rebote (sesiones con 1 sola página vista en últimos 7 días)
            $totalSessions = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM (SELECT session_id FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY session_id) s"
            )['cnt'] ?? 0);

            $singlePageSessions = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM (SELECT session_id, COUNT(*) as pv FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY session_id HAVING pv = 1) s"
            )['cnt'] ?? 0);

            $bounceRate = $totalSessions > 0 ? round(($singlePageSessions / $totalSessions) * 100) : 0;
        } catch (\Throwable $e) {
            error_log('[Analytics] Error getStats: ' . $e->getMessage());
            $today = $yesterday = $week = $month = $total = $uniqueToday = $avgTime = $bounceRate = 0;
            $totalSessions = $singlePageSessions = 0;
        }

        return compact('today', 'yesterday', 'week', 'month', 'total', 'uniqueToday', 'avgTime', 'bounceRate', 'totalSessions', 'singlePageSessions');
    }

    private function getTopPages(int $limit = 10): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT page_url,
                        MAX(page_title) as page_title,
                        COUNT(*) as views,
                        COALESCE(SUM(time_on_page_seconds), 0) as total_time,
                        COALESCE(AVG(time_on_page_seconds), 0) as avg_time,
                        COUNT(DISTINCT session_id) as unique_visitors
                 FROM analytics_pageviews
                 WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY page_url
                 ORDER BY views DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (\Throwable $e) {
            error_log('[Analytics] Error getTopPages: ' . $e->getMessage());
            return [];
        }
    }

    private function getRecentSessions(int $limit = 20): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT p1.*,
                        (SELECT COUNT(*) FROM analytics_pageviews p2 WHERE p2.session_id = p1.session_id) as session_views
                 FROM analytics_pageviews p1
                 WHERE p1.is_entry = 1
                 ORDER BY p1.visited_at DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (\Throwable $e) {
            error_log('[Analytics] Error getRecentSessions: ' . $e->getMessage());
            return [];
        }
    }

    private function getTrafficSources(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT referer_type,
                        COUNT(*) as cnt,
                        COUNT(DISTINCT session_id) as unique_sessions
                 FROM analytics_pageviews
                 WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY referer_type
                 ORDER BY cnt DESC"
            );
        } catch (\Throwable $e) {
            error_log('[Analytics] Error getTrafficSources: ' . $e->getMessage());
            return [];
        }
    }

    private function getDailyTrend(int $days = 14): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT DATE(visited_at) as date,
                        COUNT(*) as views,
                        COUNT(DISTINCT session_id) as visitors
                 FROM analytics_pageviews
                 WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY DATE(visited_at)
                 ORDER BY date ASC",
                [$days]
            );
        } catch (\Throwable $e) {
            error_log('[Analytics] Error getDailyTrend: ' . $e->getMessage());
            return [];
        }
    }

    // ─── Helpers ───

    private function classifyReferer(?string $referer): string
    {
        if ($referer === null || $referer === '') {
            return 'direct';
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if ($host === false || $host === null) {
            return 'direct';
        }

        // Buscar motor de búsqueda
        $searchEngines = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'baidu.', 'yandex.', 'ecosia.'];
        foreach ($searchEngines as $engine) {
            if (str_contains($host, $engine)) {
                return 'search';
            }
        }

        // Redes sociales (parcial)
        $social = ['facebook.', 'twitter.', 'x.com', 'instagram.', 'linkedin.', 'pinterest.', 'tiktok.', 'reddit.'];
        foreach ($social as $s) {
            if (str_contains($host, $s)) {
                return 'social';
            }
        }

        // ¿Es nuestro propio dominio?
        $ourHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if ($host === $ourHost || str_ends_with($host, '.' . $ourHost)) {
            return 'internal';
        }

        return 'external';
    }
}
