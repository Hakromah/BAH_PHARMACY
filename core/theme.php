<?php
/**
 * Tema Yönetimi
 */

function getThemes()
{
    return [
        'dark' => [
            'name' => 'Koyu (Varsayılan)',
            'colors' => [
                '--body-bg' => '#0f1e2d',
                '--card-bg' => '#162333',
                '--sidebar-bg' => '#0d1b2a',
                '--text-primary' => '#e2e8f0',
                '--text-muted' => '#64748b',
                '--accent' => '#0ea5e9',
                '--card-border' => 'rgba(255,255,255,0.07)',
            ]
        ],
        'light' => [
            'name' => 'Açık (Light)',
            'colors' => [
                '--body-bg' => '#f1f5f9',
                '--card-bg' => '#ffffff',
                '--sidebar-bg' => '#ffffff',
                '--text-primary' => '#1e293b',
                '--text-muted' => '#64748b',
                '--accent' => '#0ea5e9',
                '--card-border' => 'rgba(0,0,0,0.08)',
            ]
        ],
        'dracula' => [
            'name' => 'Dracula',
            'colors' => [
                '--body-bg' => '#282a36',
                '--card-bg' => '#44475a',
                '--sidebar-bg' => '#282a36',
                '--text-primary' => '#f8f8f2',
                '--text-muted' => '#bfbfbf',
                '--accent' => '#bd93f9',
                '--card-border' => 'rgba(255,255,255,0.1)',
            ]
        ],
        'forest' => [
            'name' => 'Orman',
            'colors' => [
                '--body-bg' => '#132a13',
                '--card-bg' => '#1b3b1b',
                '--sidebar-bg' => '#102410',
                '--text-primary' => '#ecf39e',
                '--text-muted' => '#a5a58d',
                '--accent' => '#90a955',
                '--card-border' => 'rgba(255,255,255,0.1)',
            ]
        ],
        'sunset' => [
            'name' => 'Gün Batımı',
            'colors' => [
                '--body-bg' => '#2a0a18',
                '--card-bg' => '#401125',
                '--sidebar-bg' => '#200713',
                '--text-primary' => '#fce4ec',
                '--text-muted' => '#f48fb1',
                '--accent' => '#ec407a',
                '--card-border' => 'rgba(255,255,255,0.05)',
            ]
        ]
    ];
}

function getCurrentTheme()
{
    return $_COOKIE['current_theme'] ?? 'dark';
}

function getThemeCSS()
{
    $themes = getThemes();
    $current = getCurrentTheme();
    $cssLines = [];

    // Light mod için kapsamlı stil düzeltmeleri
    $lightModeFix = "
        body { color: #1e293b !important; }
        .sidebar-nav li a { color: #475569 !important; }
        .sidebar-nav li a:hover, .sidebar-nav li a.active { color: var(--accent) !important; background: rgba(14,165,233,0.1) !important; }
        .brand-text { color: #1e293b !important; }
        .table-dark-custom th { background: rgba(0,0,0,0.04) !important; color: #64748b !important; }
        .table-dark-custom td { border-bottom: 1px solid rgba(0,0,0,0.04) !important; color: #1e293b !important; }
        .form-label-dark { color: #475569 !important; }
        .form-control-dark, .form-select-dark { background: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #1e293b !important; }
        .form-control-dark::placeholder { color: #94a3b8 !important; }
        .form-select-dark option { background: #ffffff !important; color: #1e293b !important; }
        .modal-dark .modal-content { background: #ffffff !important; color: #1e293b !important; border-color:#e2e8f0 !important; }
        .text-white-50 { color: #64748b !important; }
        .panel { background: #ffffff !important; border-color: #e2e8f0 !important; }
        .panel-header { border-bottom-color: #f1f5f9 !important; color: #1e293b !important; }
        .topbar { background: #ffffff !important; border-bottom-color: #e2e8f0 !important; }
        .topbar-title { color: #1e293b !important; }
        
        /* Alerts */
        .alert-danger { background-color: #fef2f2 !important; border-color: #fecaca !important; color: #991b1b !important; }
        .alert-danger hr { border-top-color: #fecaca !important; }
        .alert-warning { background-color: #fffbeb !important; border-color: #fde68a !important; color: #92400e !important; }
        .alert-success { background-color: #f0fdf4 !important; border-color: #bbf7d0 !important; color: #166534 !important; }
        
        /* Arama Sonuçları */
        #productResults, #customerResults { background: #ffffff !important; border: 1px solid #cbd5e1 !important; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1) !important; }
        #productResults div, #customerResults div { border-bottom: 1px solid #f1f5f9 !important; }
        #productResults strong, #customerResults strong { color: #1e293b !important; }
    ";

    $isLightBg = false;

    if ($current === 'custom') {
        $custom = json_decode($_COOKIE['custom_theme_colors'] ?? '{}', true);
        if ($custom) {
            $cssLines[] = "--body-bg: " . ($custom['body'] ?? '#0f1e2d') . " !important;";
            $cssLines[] = "--card-bg: " . ($custom['card'] ?? '#162333') . " !important;";
            $cssLines[] = "--sidebar-bg: " . ($custom['sidebar'] ?? '#0d1b2a') . " !important;";
            $cssLines[] = "--text-primary: " . ($custom['text'] ?? '#e2e8f0') . " !important;";
            $cssLines[] = "--accent: " . ($custom['accent'] ?? '#0ea5e9') . " !important;";

            // Eğer body bg beyaza yakınsa tablo renklerini filan toparla
            if (isset($custom['body']) && strtoupper($custom['body']) === '#FFFFFF') {
                $isLightBg = true;
            }
        }
    } else if (isset($themes[$current]) && $current !== 'dark') {
        $colors = $themes[$current]['colors'];
        foreach ($colors as $k => $v) {
            $cssLines[] = "{$k}: {$v} !important;";
        }
        if ($current === 'light') {
            $isLightBg = true;
        }
    }

    if (!empty($cssLines)) {
        $out = "<style>:root {\n  " . implode("\n  ", $cssLines) . "\n}\n";
        if ($isLightBg) {
            $out .= $lightModeFix;
        }
        $out .= "</style>";
        return $out;
    }

    return "";
}

function setTheme($theme, $customColors = [])
{
    setcookie('current_theme', $theme, time() + (86400 * 365), "/");
    if ($theme === 'custom' && !empty($customColors)) {
        setcookie('custom_theme_colors', json_encode($customColors), time() + (86400 * 365), "/");
    }
}
