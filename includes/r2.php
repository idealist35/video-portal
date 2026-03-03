<?php
/**
 * Minimal S3-compatible client for Cloudflare R2
 * 
 * Implements AWS Signature V4 for presigned URLs.
 * No external SDK required — pure PHP + curl.
 */

require_once __DIR__ . '/config.php';

class R2Client
{
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $endpoint;
    private string $region;

    public function __construct()
    {
        $this->accessKey = R2_ACCESS_KEY;
        $this->secretKey = R2_SECRET_KEY;
        $this->bucket    = R2_BUCKET;
        $this->endpoint  = R2_ENDPOINT;
        $this->region    = R2_REGION;
    }

    /**
     * Generate a presigned GET URL for downloading/streaming a file.
     */
    public function getPresignedUrl(string $key, int $ttl = 1800): string
    {
        return $this->presign('GET', $key, $ttl);
    }

    /**
     * Generate a presigned PUT URL for uploading a file.
     */
    public function getUploadUrl(string $key, int $ttl = 3600): string
    {
        return $this->presign('PUT', $key, $ttl);
    }

    /**
     * Upload a local file to R2.
     */
    public function upload(string $key, string $filePath, string $contentType = 'application/octet-stream'): bool
    {
        $url = $this->endpoint . '/' . $this->bucket . '/' . ltrim($key, '/');
        $body = file_get_contents($filePath);
        $now = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        $headers = [
            'host'                 => parse_url($url, PHP_URL_HOST),
            'x-amz-date'          => $now,
            'x-amz-content-sha256' => hash('sha256', $body),
            'content-type'        => $contentType,
            'content-length'      => (string) strlen($body),
        ];

        $authHeader = $this->buildAuthHeader('PUT', '/' . $this->bucket . '/' . ltrim($key, '/'), '', $headers, $body, $now, $date);
        $headers['authorization'] = $authHeader;

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = $k . ': ' . $v;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Delete a file from R2.
     */
    public function delete(string $key): bool
    {
        $url = $this->endpoint . '/' . $this->bucket . '/' . ltrim($key, '/');
        $now = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        $headers = [
            'host'                 => parse_url($url, PHP_URL_HOST),
            'x-amz-date'          => $now,
            'x-amz-content-sha256' => hash('sha256', ''),
        ];

        $authHeader = $this->buildAuthHeader('DELETE', '/' . $this->bucket . '/' . ltrim($key, '/'), '', $headers, '', $now, $date);
        $headers['authorization'] = $authHeader;

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = $k . ': ' . $v;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    // ── Presigned URL (AWS Signature V4) ─────────────────────

    private function presign(string $method, string $key, int $ttl): string
    {
        $now = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $credential = $this->accessKey . '/' . $date . '/' . $this->region . '/s3/aws4_request';
        $path = '/' . $this->bucket . '/' . ltrim($key, '/');

        $queryParams = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $credential,
            'X-Amz-Date'          => $now,
            'X-Amz-Expires'       => (string) $ttl,
            'X-Amz-SignedHeaders'  => 'host',
        ];
        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $host = parse_url($this->endpoint, PHP_URL_HOST);

        // Canonical request
        $canonicalRequest = implode("\n", [
            $method,
            $this->uriEncodePath($path),
            $queryString,
            'host:' . $host . "\n",
            'host',
            'UNSIGNED-PAYLOAD',
        ]);

        // String to sign
        $scope = $date . '/' . $this->region . '/s3/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $now,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        // Signing key
        $signingKey = $this->getSigningKey($date);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return $this->endpoint . $path . '?' . $queryString . '&X-Amz-Signature=' . $signature;
    }

    // ── Auth Header (for upload/delete) ──────────────────────

    private function buildAuthHeader(string $method, string $path, string $query, array $headers, string $body, string $now, string $date): string
    {
        // Signed headers (sorted, lowercase)
        $signedHeaderNames = array_keys($headers);
        sort($signedHeaderNames);
        // Exclude 'authorization' and 'content-length' from signing
        $signedHeaderNames = array_filter($signedHeaderNames, fn($h) => !in_array($h, ['authorization', 'content-length']));
        $signedHeaders = implode(';', $signedHeaderNames);

        // Canonical headers
        $canonicalHeaders = '';
        foreach ($signedHeaderNames as $name) {
            $canonicalHeaders .= $name . ':' . trim($headers[$name]) . "\n";
        }

        $payloadHash = hash('sha256', $body);

        $canonicalRequest = implode("\n", [
            $method,
            $this->uriEncodePath($path),
            $query,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = $date . '/' . $this->region . '/s3/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $now,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->getSigningKey($date);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $credential = $this->accessKey . '/' . $scope;

        return "AWS4-HMAC-SHA256 Credential={$credential}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    }

    // ── Helpers ──────────────────────────────────────────────

    private function getSigningKey(string $date): string
    {
        $kDate    = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    /**
     * URI-encode path segments (but not the slashes).
     */
    private function uriEncodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}

/**
 * Global helper — get R2 client singleton.
 */
function getR2(): R2Client
{
    static $client = null;
    if ($client === null) {
        $client = new R2Client();
    }
    return $client;
}
