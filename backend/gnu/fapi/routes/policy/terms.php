<?php
// GET /api/policy/terms
function GET()
{
    global $config;

    json_return([
        'co_id' => 'terms',
        'co_subject' => '회원가입약관',
        'co_content' => $config['cf_stipulation']
    ], 200, '00000');
}
