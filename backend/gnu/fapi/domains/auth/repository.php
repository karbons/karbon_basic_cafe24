<?php
require_once G5_API_PATH . '/shared/db.php';

function auth_repo_save_refresh_token($mb_id, $uuid, $agent, $token, $fcm_token = '', $device_model = '', $os_version = '')
{
    return db_query(
        "INSERT INTO g5_member_refresh 
         SET mb_id = ?, uuid = ?, agent = ?, refresh_token = ?, 
             fcm_token = ?, device_model = ?, os_version = ?, reg_datetime = NOW()"
    )
        ->bind($mb_id)
        ->bind($uuid)
        ->bind($agent)
        ->bind($token)
        ->bind($fcm_token)
        ->bind($device_model)
        ->bind($os_version)
        ->execute();
}

function auth_repo_find_refresh_token($uuid)
{
    return db_query("SELECT * FROM g5_member_refresh WHERE uuid = ?")
        ->bind($uuid)
        ->fetch_optional();
}

function auth_repo_delete_refresh_token($mb_id)
{
    return db_query("DELETE FROM g5_member_refresh WHERE mb_id = ?")
        ->bind($mb_id)
        ->execute();
}
