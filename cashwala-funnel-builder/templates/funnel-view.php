<?php
if (! defined('ABSPATH')) {
    exit;
}

if (empty($context['step'])) {
    return;
}

$step = $context['step'];
$funnel = $context['funnel'];
$next_url = $context['next_url'];

$partial = CWFB_PLUGIN_PATH . 'templates/step-' . $step . '.php';
if (file_exists($partial)) {
    include $partial;
}
