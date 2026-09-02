<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| Vite asset helper
| -------------------------------------------------------------------
| Outputs the correct <script>/<link> tags for the Tailwind
| frontend, depending on environment:
|
|  - APP_ENV=development and the Vite dev server (the "node" Docker
|    service) is reachable  -> point at the dev server for HMR.
|  - otherwise               -> read public/build/.vite/manifest.json
|    (produced by `npm run build`) and load the hashed, built assets.
|
| Usage in a view:
|   <?php echo vite('resources/js/app.js'); ?>
*/

if (!function_exists('vite_dev_server_is_up')) {
    function vite_dev_server_is_up($host = 'node', $port = 5173, $timeout = 0.2)
    {
        $conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($conn) {
            fclose($conn);
            return true;
        }
        return false;
    }
}

if (!function_exists('vite')) {
    function vite($entry = 'resources/js/app.js')
    {
        $appEnv = getenv('APP_ENV') ?: 'production';
        $devServerUrl = 'http://localhost:5173';

        if ($appEnv === 'development' && vite_dev_server_is_up()) {
            return '<script type="module" src="' . $devServerUrl . '/@vite/client"></script>' . "\n"
                 . '<script type="module" src="' . $devServerUrl . '/' . ltrim($entry, '/') . '"></script>';
        }

        $manifestPath = ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . '.vite' . DIRECTORY_SEPARATOR . 'manifest.json';

        if (!file_exists($manifestPath)) {
            return '<!-- Vite manifest not found. Run "npm run build" (or start the node/vite dev service). -->';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!isset($manifest[$entry])) {
            return '<!-- Vite entry "' . htmlspecialchars($entry) . '" not found in manifest. -->';
        }

        $tags = array();
        $chunk = $manifest[$entry];

        if (!empty($chunk['css'])) {
            foreach ($chunk['css'] as $css) {
                $tags[] = '<link rel="stylesheet" href="/build/' . $css . '">';
            }
        }

        $tags[] = '<script type="module" src="/build/' . $chunk['file'] . '"></script>';

        return implode("\n", $tags);
    }
}
