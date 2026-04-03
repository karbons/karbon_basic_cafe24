<?php
// POST /api/spellcheck
function POST()
{
    header('Content-Type: application/json; charset=utf-8');

    // JSON 입력 받기
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['text']) || empty($input['text'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '검사할 텍스트를 입력해주세요.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $text = $input['text'];

    // 텍스트 길이 제한 (다음 API는 약 1000자 제한)
    if (mb_strlen($text) > 10000) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '텍스트가 너무 깁니다. 10000자 이하로 입력해주세요.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $result = check_spell_daum($text);

        echo json_encode([
            'success' => true,
            'corrections' => $result['corrections'],
            'corrected_text' => $result['corrected_text'],
            'original_text' => $text
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => '맞춤법 검사 중 오류가 발생했습니다: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * 다음 맞춤법 검사기 API 호출 (hanfix와 동일한 방식)
 * @param string $text 검사할 텍스트
 * @return array 교정 결과
 */
function check_spell_daum($text)
{
    // 텍스트를 청크로 나눠서 처리 (다음 API는 약 500자 이하가 안정적)
    $maxChunkSize = 500;
    $allCorrections = [];
    $correctedText = $text;

    // 줄바꿈으로 나누기
    $lines = preg_split('/\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);

    $chunks = [];
    $currentChunk = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed))
            continue;

        if (mb_strlen($currentChunk . "\n" . $trimmed) <= $maxChunkSize) {
            $currentChunk .= ($currentChunk ? "\n" : '') . $trimmed;
        } else {
            if ($currentChunk) {
                $chunks[] = $currentChunk;
            }
            // 줄 자체가 너무 긴 경우
            if (mb_strlen($trimmed) > $maxChunkSize) {
                $parts = mb_str_split($trimmed, $maxChunkSize);
                foreach ($parts as $part) {
                    $chunks[] = $part;
                }
                $currentChunk = '';
            } else {
                $currentChunk = $trimmed;
            }
        }
    }
    if ($currentChunk) {
        $chunks[] = $currentChunk;
    }

    if (empty($chunks)) {
        $chunks = [trim($text)];
    }

    // 각 청크 검사
    foreach ($chunks as $chunk) {
        if (empty(trim($chunk)))
            continue;

        $result = call_daum_api($chunk);

        foreach ($result as $error) {
            $allCorrections[] = $error;

            // 교정된 텍스트 생성
            if (!empty($error['token']) && !empty($error['suggestions'])) {
                $correctedText = str_replace(
                    $error['token'],
                    $error['suggestions'][0],
                    $correctedText
                );
            }
        }
    }

    // 중복 제거
    $uniqueCorrections = [];
    $seen = [];
    foreach ($allCorrections as $correction) {
        $key = $correction['token'];
        if (!isset($seen[$key])) {
            $uniqueCorrections[] = $correction;
            $seen[$key] = true;
        }
    }

    return [
        'corrections' => $uniqueCorrections,
        'corrected_text' => $correctedText
    ];
}

/**
 * 다음 맞춤법 검사 API 호출
 * @param string $text 검사할 텍스트
 * @return array 파싱된 오류 목록
 */
function call_daum_api($text)
{
    $url = 'https://dic.daum.net/grammar_checker.do';

    // POST 데이터
    $postData = http_build_query([
        'sentence' => $text
    ]);

    // cURL 요청
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ko-KR,ko;q=0.9',
            'Referer: https://dic.daum.net/grammar_checker.do'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('API 요청 실패: ' . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception('API 응답 오류: HTTP ' . $httpCode);
    }

    return parse_daum_html($response);
}

/**
 * 다음 맞춤법 검사 HTML 응답 파싱
 * hanfix와 동일한 방식: div.cont_spell a 태그에서 data-error-input, data-error-output 추출
 * @param string $html HTML 응답
 * @return array 파싱된 오류 목록
 */
function parse_daum_html($html)
{
    $corrections = [];

    // DOMDocument 사용
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // hanfix와 동일한 셀렉터: div.cont_spell a
    $nodes = $xpath->query("//div[contains(@class, 'cont_spell')]//a");

    foreach ($nodes as $node) {
        $errorInput = $node->getAttribute('data-error-input');
        $errorOutput = $node->getAttribute('data-error-output');

        // 케멀케이스도 확인
        if (empty($errorInput)) {
            $errorInput = $node->getAttribute('data-errorinput');
        }
        if (empty($errorOutput)) {
            $errorOutput = $node->getAttribute('data-erroroutput');
        }

        if (empty($errorInput))
            continue;

        // 설명 추출
        $explanation = '';
        $helpNode = $xpath->query(".//li", $node);
        if ($helpNode->length > 0) {
            $explanation = trim($helpNode->item(0)->textContent);
        }

        $corrections[] = [
            'token' => $errorInput,
            'suggestions' => $errorOutput ? [$errorOutput] : [],
            'info' => $explanation ?: '맞춤법/문법 오류'
        ];
    }

    // 위 방식이 실패하면 정규식으로 추출 시도
    if (empty($corrections)) {
        // data-error-input="..." data-error-output="..." 패턴
        preg_match_all('/data-error-input=["\']([^"\']+)["\']\s*data-error-output=["\']([^"\']*)["\']/', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $corrections[] = [
                'token' => html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'),
                'suggestions' => $match[2] ? [html_entity_decode($match[2], ENT_QUOTES, 'UTF-8')] : [],
                'info' => '맞춤법/문법 오류'
            ];
        }
    }

    return $corrections;
}
