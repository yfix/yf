<?php

require_once dirname(__DIR__) . '/yf_unit_tests_setup.php';

class assets_urls_check_test extends yf\tests\wrapper
{
    public $TIMEOUT = 5;

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    /**
     * Fetch URL sizes using multi-curl for parallel processing
     */
    private function fetchUrlSizes(array $urls): array
    {
        // Initialize multi-curl handle
        $multiHandle = curl_multi_init();
        $handles = [];
        $results = [];

        // Create curl handles for each URL
        foreach ($urls as $originalUrl) {
            $url = $this->normalizeUrl($originalUrl);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => $this->TIMEOUT,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $handles[$originalUrl] = $ch;
        }

        // Execute all requests
        do {
            $status = curl_multi_exec($multiHandle, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        while ($active && $status === CURLM_OK) {
            if (curl_multi_select($multiHandle) === -1) {
                usleep(100);
            }

            do {
                $status = curl_multi_exec($multiHandle, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
        }

        // Collect results
        foreach ($handles as $originalUrl => $ch) {
            $content = curl_multi_getcontent($ch);
            $size = $content ? strlen($content) : 0;

            // If first attempt fails, retry with longer timeout
            if ($size === 0) {
                $retryUrl = $this->normalizeUrl($originalUrl);
                $retryCh = curl_init($retryUrl);
                curl_setopt_array($retryCh, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->TIMEOUT * 2,
                    CURLOPT_CONNECTTIMEOUT => $this->TIMEOUT * 2,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ]);
                $retryContent = curl_exec($retryCh);
                $size = $retryContent ? strlen($retryContent) : 0;
                curl_close($retryCh);
            }

            $results[] = [
                'url' => $originalUrl,
                'size' => $size,
            ];

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    public function test_do()
    {
        ini_set('default_socket_timeout', $this->TIMEOUT);

        $data = require YF_PATH . '.dev/scripts/assets/assets_urls_collect.php';

        $this->assertIsArray($data['urls']);
        $this->assertNotEmpty($data['urls']);

        $this->assertIsArray($data['paths']);
        $this->assertNotEmpty($data['paths']);

        $urls = $data['urls'];
        $total = count($urls);
        $processed = 0;

        // Run URL size checks concurrently
        $results = $this->fetchUrlSizes($urls);

        foreach ($results as $result) {
            $processed++;
            $url = $result['url'];
            $size = $result['size'];

            // Progress tracking
            // fwrite(STDERR, sprintf("%d/%d | Processing %s\n", $processed, $total, $url));

            // Verify size for each path associated with the URL
            foreach ($data['paths'][$url] as $path) {
                $this->assertTrue(($size > 50), "$url | $path | $size");
            }
        }
    }
}
