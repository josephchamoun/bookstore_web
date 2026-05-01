<?php

define('FIREBASE_PROJECT_ID', 'bookstore-firebase-326b4');
define('FIREBASE_CREDENTIALS', __DIR__ . '/serviceAccountKey.json');

function getAccessToken(): string {
    static $token = null;
    static $expiry = 0;

    if ($token && time() < $expiry - 60) return $token;

    $credentials = json_decode(file_get_contents(FIREBASE_CREDENTIALS), true);

    $now     = time();
    $header  = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
    $payload = rtrim(strtr(base64_encode(json_encode([
        'iss'   => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/datastore https://www.googleapis.com/auth/cloud-platform',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ])), '+/', '-_'), '=');

    $toSign = "$header.$payload";
    openssl_sign($toSign, $signature, $credentials['private_key'], 'SHA256');
    $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    $jwt = "$toSign.$signature";

    // ← CHANGED: use file_get_contents instead of curl
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            'ignore_errors' => true,
        ]
    ];

    $context  = stream_context_create($options);
    $response = json_decode(file_get_contents('https://oauth2.googleapis.com/token', false, $context), true);

    $token  = $response['access_token'];
    $expiry = $now + ($response['expires_in'] ?? 3600);

    return $token;
}

function firestoreRequest(string $method, string $path, array $body = null): array {
    $url     = 'https://firestore.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/databases/(default)/documents/' . $path;
    $token   = getAccessToken();
    $headers = "Authorization: Bearer $token\r\nContent-Type: application/json\r\n";

    $options = [
        'http' => [
            'method'  => $method,
            'header'  => $headers,
            'ignore_errors' => true,
        ]
    ];

    if ($body !== null) {
        $options['http']['content'] = json_encode($body);
    }

    $context = stream_context_create($options);
    $result  = file_get_contents($url, false, $context);
    return json_decode($result, true) ?? [];
}

// ── Firestore value helpers ───────────────────────────────────────────────────

function fsValue(mixed $value): array {
    if (is_bool($value))   return ['booleanValue'   => $value];
    if (is_int($value))    return ['integerValue'   => (string)$value];
    if (is_float($value))  return ['doubleValue'    => $value];
    if (is_null($value))   return ['nullValue'      => null];
    if (is_array($value)) {
        // Check if it's a list (sequential array)
        if (array_keys($value) === range(0, count($value) - 1)) {
            return ['arrayValue' => ['values' => array_map('fsValue', $value)]];
        }
        // It's a map
        $fields = [];
        foreach ($value as $k => $v) $fields[$k] = fsValue($v);
        return ['mapValue' => ['fields' => $fields]];
    }
    return ['stringValue' => (string)$value];
}

function fsDoc(array $data): array {
    $fields = [];
    foreach ($data as $k => $v) $fields[$k] = fsValue($v);
    return ['fields' => $fields];
}

function parseValue(array $value): mixed {
    if (isset($value['stringValue']))  return $value['stringValue'];
    if (isset($value['integerValue'])) return (int)$value['integerValue'];
    if (isset($value['doubleValue']))  return (float)$value['doubleValue'];
    if (isset($value['booleanValue'])) return $value['booleanValue'];
    if (isset($value['nullValue']))    return null;
    if (isset($value['timestampValue'])) return $value['timestampValue'];
    if (isset($value['arrayValue'])) {
        $items = $value['arrayValue']['values'] ?? [];
        return array_map('parseValue', $items);
    }
    if (isset($value['mapValue'])) {
        return parseDoc(['fields' => $value['mapValue']['fields'] ?? []]);
    }
    return null;
}

function parseDoc(array $doc): array {
    $result = [];
    foreach ($doc['fields'] ?? [] as $key => $value) {
        $result[$key] = parseValue($value);
    }
    return $result;
}

function getDocId(array $doc): string {
    $name = $doc['name'] ?? '';
    return basename($name);
}

// ── High-level helpers ────────────────────────────────────────────────────────

function fsGetCollection(string $collection): array {
    $result = firestoreRequest('GET', $collection);
    $docs   = [];
    foreach ($result['documents'] ?? [] as $doc) {
        $data        = parseDoc($doc);
        $data['id']  = getDocId($doc);
        $docs[]      = $data;
    }
    return $docs;
}

function fsGetDocument(string $collection, string $id): ?array {
    $result = firestoreRequest('GET', "$collection/$id");
    if (isset($result['error'])) return null;
    $data       = parseDoc($result);
    $data['id'] = getDocId($result);
    return $data;
}

function fsAddDocument(string $collection, array $data): string {
    $result = firestoreRequest('POST', $collection, fsDoc($data));
    return getDocId($result);
}

function fsSetDocument(string $collection, string $id, array $data): void {
    firestoreRequest('PATCH', "$collection/$id", fsDoc($data));
}

function fsUpdateDocument(string $collection, string $id, array $data): void {
    $fields     = array_keys($data);
    $updateMask = implode('&updateMask.fieldPaths=', array_map('urlencode', $fields));
    $url        = "$collection/$id?updateMask.fieldPaths=" . implode('&updateMask.fieldPaths=', array_map('urlencode', $fields));
    firestoreRequest('PATCH', $url, fsDoc($data));
}

function fsDeleteDocument(string $collection, string $id): void {
    firestoreRequest('DELETE', "$collection/$id");
}

function fsQuery(string $collection, string $field, string $op, mixed $value): array {
    $opMap = [
        '='  => 'EQUAL',
        '!=' => 'NOT_EQUAL',
        '<'  => 'LESS_THAN',
        '<=' => 'LESS_THAN_OR_EQUAL',
        '>'  => 'GREATER_THAN',
        '>=' => 'GREATER_THAN_OR_EQUAL',
    ];

    $body = [
        'structuredQuery' => [
            'from'  => [['collectionId' => $collection]],
            'where' => [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $field],
                    'op'    => $opMap[$op] ?? 'EQUAL',
                    'value' => fsValue($value),
                ]
            ]
        ]
    ];

    $result = firestoreRequest('POST', ':runQuery', $body);
    $docs   = [];
    foreach ($result as $item) {
        if (!isset($item['document'])) continue;
        $data       = parseDoc($item['document']);
        $data['id'] = getDocId($item['document']);
        $docs[]     = $data;
    }
    return $docs;
}