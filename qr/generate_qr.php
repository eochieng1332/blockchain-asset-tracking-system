<?php
require_once dirname(__DIR__) . '/config/db.php';

// Include PHP QR Code library
require_once dirname(__DIR__) . '/vendor/phpqrcode/qrlib.php';

function generateQRCode($asset_id, $asset_tag) {
    $qr_dir = dirname(__DIR__) . '/qr/qrcodes/';
    
    // Create directory if it doesn't exist
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0777, true);
    }
    
    $qr_file = $qr_dir . "asset_{$asset_id}.png";
    
    // Generate verification URL
    $verification_url = BASE_URL . "verify/verify_asset.php?id=" . $asset_id;
    
    // Generate QR code
    QRcode::png($verification_url, $qr_file, QR_ECLEVEL_L, 10, 2);
    
    return $qr_file;
}

// If called directly with parameters
if (isset($_GET['asset_id']) && isset($_GET['asset_tag'])) {
    $asset_id = $_GET['asset_id'];
    $asset_tag = $_GET['asset_tag'];
    $qr_file = generateQRCode($asset_id, $asset_tag);
    
    if (file_exists($qr_file)) {
        header('Content-Type: image/png');
        readfile($qr_file);
    }
}
?>