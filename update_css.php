<?php
// This script updates the neumorphism.css file with status color palettes
$file = 'assets/css/neumorphism.css';
$content = file_get_contents($file);

$status_palette = "\n/* Status Palette & Tracking Cards (Phase 9) */\n" .
".status-pending { background: #6c757d !important; color: #fff !important; }\n" .
".status-blue, .status-picked-up { background: #3498db !important; color: #fff !important; }\n" .
".status-yellow, .status-out-delivery { background: #f1c40f !important; color: #000 !important; }\n" .
".status-green, .status-delivered { background: #4CAF50 !important; color: #fff !important; }\n" .
".status-transit { background: #7952b3 !important; color: #fff !important; }\n" .
".status-returned { background: #e74c3c !important; color: #fff !important; }\n" .
".status-cancelled { background: #95a5a6 !important; color: #fff !important; }\n" .
"\n.tracking-info-card {\n" .
"    background: var(--bg-color);\n" .
"    border-radius: 8px;\n" .
"    padding: 24px;\n" .
"    box-shadow: 0 2px 6px rgba(0,0,0,0.05);\n" .
"    border: 1px solid rgba(0,0,0,0.02);\n" .
"    margin-bottom: 1.5rem;\n" .
"}\n" .
"\n[data-theme='dark'] .tracking-info-card {\n" .
"    background: #222;\n" .
"    box-shadow: 0 4px 12px rgba(0,0,0,0.3);\n" .
"}\n" .
"\n.alert-card {\n" .
"    border-left: 5px solid var(--primary-color);\n" .
"}\n" .
".alert-card.delayed { border-left-color: #f1c40f; }\n" .
".alert-card.delivered { border-left-color: #4CAF50; }\n" .
"\n.eta-highlight {\n" .
"    font-size: 2.2rem;\n" .
"    font-weight: 800;\n" .
"    color: var(--primary-color);\n" .
"    letter-spacing: -1px;\n" .
"}\n";

if (strpos($content, 'Phase 9') === false) {
    file_put_contents($file, $content . $status_palette);
    echo "CSS updated successfully.";
} else {
    echo "CSS already updated.";
}