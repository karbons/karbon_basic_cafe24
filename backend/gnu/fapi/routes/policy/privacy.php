<?php
// GET /api/policy/privacy
function GET()
{
    global $config;

    json_return([
        'co_id' => 'privacy',
        'co_subject' => '개인정보처리방침안내',
        'co_content' => $config['cf_privacy']
    ], 200, '00000');
}
