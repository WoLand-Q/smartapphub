<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '') { echo json_encode([]); exit; }

$rows = db_all("
  SELECT type, rid, title
  FROM search_all
  WHERE search_all MATCH ?
  ORDER BY rank
  LIMIT 8
", [$q.'*']);

$out = [];
foreach ($rows as $r) {
    if ($r['type']==='news') {
        $url = 'news_view.php?id='.(int)$r['rid'];
    } elseif ($r['type']==='doc') {
        $url = 'docs_view.php?id='.(int)$r['rid'];
    } elseif ($r['type']==='plugin') {
        $slug = db_one('SELECT slug FROM plugins WHERE id=?', [(int)$r['rid']])['slug'] ?? '';
        $url = $slug ? 'plugin_view.php?slug='.rawurlencode($slug) : '#';
    } else $url = '#';

    $out[] = [
        'type'  => $r['type'],
        'title' => $r['title'],
        'url'   => $url
    ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
