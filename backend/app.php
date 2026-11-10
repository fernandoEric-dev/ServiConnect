<?php
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/search.php';
function app_feedback(): void {
    $feedback = $_SESSION['order_feedback'] ?? null; unset($_SESSION['order_feedback']);
    if ($feedback) echo '<div role="' . (!empty($feedback['error']) ? 'alert' : 'status') . '" class="notice ' . (!empty($feedback['error']) ? 'notice-error' : '') . '">' . order_escape($feedback['message']) . '</div>';
}
function app_old(): array { $old = $_SESSION['order_old'] ?? []; unset($_SESSION['order_old']); return $old; }
function app_value(array $old, string $key, $default = ''): string { return order_escape(is_scalar($old[$key] ?? $default) ? ($old[$key] ?? $default) : ''); }
function app_date(?string $date, bool $time = false): string {
    if (!$date) return 'A combinar';
    $value = new DateTimeImmutable($date,new DateTimeZone('UTC'));
    if (strlen($date)>10) $value = $value->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    return $value->format($time ? 'd/m/Y H:i' : 'd/m/Y');
}
function app_map_points(array $providers): array {
    return array_map(static fn($item)=>array_intersect_key($item,array_flip(['usuario_id','nome_empresa','latitude','longitude'])), $providers);
}
function app_tile_attributes(): string {
    $url = app_config()['map_tile_url'];
    if (strpos($url,'https://')!==0 || preg_match('/[\s<>"\x27]/',$url)) $url='https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    return ' data-tile-url="'.order_escape($url).'"';
}
