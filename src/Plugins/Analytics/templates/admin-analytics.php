<?php declare(strict_types=1); ?>
<div class="analytics-dashboard">
    <div class="admin-header">
        <h1>📊 Analytics</h1>
        <p class="text-muted">Estadísticas de visitas</p>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="analytics-grid">
        <div class="analytics-card">
            <div class="analytics-card-value"><?= $escape($stats['today']) ?></div>
            <div class="analytics-card-label">Hoy</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-card-value"><?= $escape($stats['uniqueToday']) ?></div>
            <div class="analytics-card-label">Visitantes únicos hoy</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-card-value"><?= $escape($stats['yesterday']) ?></div>
            <div class="analytics-card-label">Ayer</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-card-value"><?= $escape($stats['week']) ?></div>
            <div class="analytics-card-label">7 días</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-card-value"><?= $escape($stats['month']) ?></div>
            <div class="analytics-card-label">30 días</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-card-value"><?= $escape($stats['total']) ?></div>
            <div class="analytics-card-label">Total acumulado</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-card-value"><?= gmdate('i\m s\s', $stats['avgTime']) ?></div>
            <div class="analytics-card-label">Tiempo medio en página</div>
        </div>
        <div class="analytics-card">
            <div class="analytics-card-value"><?= $escape($stats['bounceRate']) ?>%</div>
            <div class="analytics-card-label">Porcentaje de rebote</div>
        </div>
    </div>

    <!-- Tendencia diaria (últimos 14 días) -->
    <div class="analytics-section">
        <h2>Tendencia diaria</h2>
        <div class="analytics-table-wrap">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Visitas</th>
                        <th>Visitantes</th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($dailyTrend)): ?>
                    <tr><td colspan="3" class="text-muted">Sin datos aún</td></tr>
<?php else: ?>
<?php foreach ($dailyTrend as $day): ?>
                    <tr>
                        <td><?= $escape($day['date'] ?? '') ?></td>
                        <td><?= $escape((string) ($day['views'] ?? 0)) ?></td>
                        <td><?= $escape((string) ($day['visitors'] ?? 0)) ?></td>
                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Páginas más vistas -->
    <div class="analytics-section">
        <h2>Páginas más vistas (7 días)</h2>
        <div class="analytics-table-wrap">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Página</th>
                        <th>Visitas</th>
                        <th>Visitantes únicos</th>
                        <th>Tiempo medio</th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($topPages)): ?>
                    <tr><td colspan="4" class="text-muted">Sin datos aún</td></tr>
<?php else: ?>
<?php foreach ($topPages as $page): ?>
<?php
    $avgTimeFormatted = $page['avg_time'] > 0
        ? gmdate('i\m s\s', (int) $page['avg_time'])
        : '-';
?>
                    <tr>
                        <td><a href="<?= $escape($page['page_url']) ?>" target="_blank"><?= $escape($page['page_title'] ?: $page['page_url']) ?></a></td>
                        <td><?= $escape((string) $page['views']) ?></td>
                        <td><?= $escape((string) ($page['unique_visitors'] ?? 0)) ?></td>
                        <td><?= $avgTimeFormatted ?></td>
                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Origen del tráfico -->
    <div class="analytics-section">
        <h2>Origen del tráfico (30 días)</h2>
        <div class="analytics-table-wrap">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Fuente</th>
                        <th>Visitas</th>
                        <th>Sesiones únicas</th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($trafficSources)): ?>
                    <tr><td colspan="3" class="text-muted">Sin datos aún</td></tr>
<?php else: ?>
<?php
    $sourceLabels = [
        'direct'   => '🟢 Directo',
        'search'   => '🔍 Búsqueda',
        'social'   => '🔵 Redes sociales',
        'external' => '🟠 Enlaces externos',
        'internal' => '⚪ Interno',
    ];
?>
<?php foreach ($trafficSources as $source): ?>
<?php $label = $sourceLabels[$source['referer_type']] ?? $source['referer_type']; ?>
                    <tr>
                        <td><?= $escape($label) ?></td>
                        <td><?= $escape((string) $source['cnt']) ?></td>
                        <td><?= $escape((string) ($source['unique_sessions'] ?? 0)) ?></td>
                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Últimas sesiones -->
    <div class="analytics-section">
        <h2>Últimas sesiones</h2>
        <div class="analytics-table-wrap">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Página de entrada</th>
                        <th>Páginas vistas</th>
                        <th>Salida</th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($recentSessions)): ?>
                    <tr><td colspan="4" class="text-muted">Sin datos aún</td></tr>
<?php else: ?>
<?php foreach ($recentSessions as $session): ?>
<?php
    $pageUrl = $session['page_url'] ?? '/';
    $time = date('H:i', strtotime($session['visited_at']));
    $views = (int) ($session['session_views'] ?? 1);
    $exitUrl = $session['exit_url'] ?? null;
    $exitType = $session['exit_type'] ?? null;

    if ($exitType === 'external_link' || $exitType === 'outbound') {
        $exitDisplay = '🔗 externo';
    } elseif ($exitType === 'internal_link' || $exitType === 'internal_nav') {
        $exitDisplay = '➡ interna';
    } elseif ($exitType === 'closed_tab' || $exitUrl === null) {
        $exitDisplay = '❌ cerrada';
    } else {
        $exitDisplay = $exitUrl ? $escape($exitUrl) : '❌ cerrada';
    }
?>
                    <tr>
                        <td><?= $escape($time) ?></td>
                        <td><a href="<?= $escape($pageUrl) ?>" target="_blank"><?= $escape(mb_substr($pageUrl, 0, 60)) ?></a></td>
                        <td><?= $escape((string) $views) ?></td>
                        <td><?= $exitDisplay ?></td>
                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}
.analytics-card {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    padding: 20px 16px;
    text-align: center;
    border-radius: 4px;
}
.analytics-card-value {
    font-size: 2em;
    font-weight: 700;
    color: #000;
    line-height: 1.2;
}
.analytics-card-label {
    font-size: 0.8em;
    color: #666;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.analytics-section {
    margin-bottom: 32px;
}
.analytics-section h2 {
    font-size: 1.1em;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 8px;
}
.analytics-table-wrap {
    overflow-x: auto;
}
.analytics-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9em;
}
.analytics-table th {
    text-align: left;
    padding: 10px 12px;
    background: #f0f0f0;
    border-bottom: 1px solid #ddd;
    font-weight: 600;
    color: #333;
}
.analytics-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    color: #333;
}
.analytics-table tr:hover td {
    background: #f5f5f5;
}
.analytics-table a {
    color: #2563eb;
    text-decoration: none;
}
.analytics-table a:hover {
    text-decoration: underline;
}
.text-muted {
    color: #999;
    font-style: italic;
    text-align: center;
    padding: 20px !important;
}
.admin-header h1 {
    font-size: 1.5em;
    font-weight: 700;
    margin-bottom: 4px;
}
.admin-header p {
    color: #666;
    margin-top: 0;
}
</style>
