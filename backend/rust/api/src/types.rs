use chrono::NaiveDateTime;
use serde::{Deserialize, Serialize};
use sqlx::{FromRow, MySqlPool};
use std::sync::Arc;

use crate::config::Config as AppConfig;

/// Member table (g5_member)
#[derive(Debug, FromRow, Serialize)]
pub struct Member {
    pub mb_id: String,
    pub mb_password: String,
    pub mb_name: String,
    pub mb_nick: String,
    pub mb_level: i8,
    pub mb_point: i32,
    pub mb_email: Option<String>,
    pub mb_phone: Option<String>,
    pub mb_hp: Option<String>,
    pub mb_datetime: Option<NaiveDateTime>,
    pub mb_lastlogin: Option<NaiveDateTime>,
    pub mb_ip: Option<String>,
    pub mb_login_ip: Option<String>,
    pub mb_1: Option<String>,
    pub mb_2: Option<String>,
    pub mb_3: Option<String>,
    pub mb_4: Option<String>,
    pub mb_5: Option<String>,
    pub mb_6: Option<String>,
    pub mb_7: Option<String>,
    pub mb_8: Option<String>,
    pub mb_9: Option<String>,
    pub mb_10: Option<String>,
}

/// Board configuration table (g5_board)
#[derive(Debug, FromRow, Serialize)]
pub struct Board {
    pub bo_table: String,
    pub bo_subject: String,
    pub bo_description: Option<String>,
    pub bo_admin: Option<String>,
    pub bo_list_level: i8,
    pub bo_read_level: i8,
    pub bo_write_level: i8,
    pub bo_reply_level: i8,
    pub bo_comment_level: i8,
    pub bo_upload_level: i8,
    pub bo_download_level: i8,
    pub bo_html_level: i8,
    pub bo_link_level: i8,
    pub bo_count_write: i32,
    pub bo_count_comment: i32,
    pub bo_page_rows: i32,
    pub bo_file_upload_count: i8,
    pub bo_file_download_count: i8,
    pub bo_use_sns: i8,
    pub bo_use_list_view: i8,
    pub bo_use_email: i8,
    pub bo_use_cert: Option<String>,
    pub bo_1: Option<String>,
    pub bo_2: Option<String>,
    pub bo_3: Option<String>,
    pub bo_4: Option<String>,
    pub bo_5: Option<String>,
    pub bo_6: Option<String>,
    pub bo_7: Option<String>,
    pub bo_8: Option<String>,
    pub bo_9: Option<String>,
    pub bo_10: Option<String>,
}

/// Board write table (g5_write_{bo_table})
#[derive(Debug, FromRow, Serialize)]
pub struct Write {
    pub wr_id: i32,
    pub wr_num: i32,
    pub wr_reply: String,
    pub wr_parent: i32,
    pub wr_is_comment: i8,
    pub wr_comment: i32,
    pub wr_comment_reply: Option<String>,
    pub ca_name: Option<String>,
    pub wr_option: Option<String>,
    pub wr_subject: String,
    pub wr_content: String,
    pub wr_link1: Option<String>,
    pub wr_link2: Option<String>,
    pub wr_link1_hit: i32,
    pub wr_link2_hit: i32,
    pub wr_hit: i32,
    pub wr_good: i32,
    pub wr_nogood: i32,
    pub mb_id: Option<String>,
    pub wr_name: String,
    pub wr_password: Option<String>,
    pub wr_email: Option<String>,
    pub wr_homepage: Option<String>,
    pub wr_datetime: NaiveDateTime,
    pub wr_ip: Option<String>,
    pub wr_1: Option<String>,
    pub wr_2: Option<String>,
    pub wr_3: Option<String>,
    pub wr_4: Option<String>,
    pub wr_5: Option<String>,
    pub wr_6: Option<String>,
    pub wr_7: Option<String>,
    pub wr_8: Option<String>,
    pub wr_9: Option<String>,
    pub wr_10: Option<String>,
}

/// Board file table (g5_board_file)
#[derive(Debug, FromRow, Serialize)]
pub struct File {
    pub bo_table: String,
    pub wr_id: i32,
    pub bf_no: i32,
    pub bf_source: String,
    pub bf_file: String,
    pub bf_file_exists: i8,
    pub bf_content: Option<String>,
    pub bf_download: i32,
    pub bf_hit: i32,
    pub bf_datetime: NaiveDateTime,
    pub bf_ip: Option<String>,
    pub mb_id: Option<String>,
}

/// Group table (g5_group)
#[derive(Debug, FromRow, Serialize)]
pub struct Group {
    pub gr_id: i32,
    pub gr_subject: String,
    pub gr_1: Option<String>,
    pub gr_2: Option<String>,
    pub gr_3: Option<String>,
    pub gr_4: Option<String>,
    pub gr_5: Option<String>,
    pub gr_6: Option<String>,
    pub gr_7: Option<String>,
    pub gr_8: Option<String>,
    pub gr_9: Option<String>,
    pub gr_10: Option<String>,
}

/// Group member table (g5_group_member)
#[derive(Debug, FromRow, Serialize)]
pub struct GroupMember {
    pub gr_id: i32,
    pub mb_id: String,
    pub gm_datetime: NaiveDateTime,
    pub gm_1: Option<String>,
    pub gm_2: Option<String>,
    pub gm_3: Option<String>,
    pub gm_4: Option<String>,
    pub gm_5: Option<String>,
}

/// Config table (g5_config)
#[derive(Debug, FromRow, Serialize)]
pub struct Config {
    pub cf_id: i32,
    pub cf_subject: String,
    pub cf_title: Option<String>,
    pub cf_description: Option<String>,
    pub cf_theme: Option<String>,
    pub cf_logo: Option<String>,
    pub cf_logo_footer: Option<String>,
    pub cf_analytics: Option<String>,
    pub cf_add_meta: Option<String>,
    pub cf_favicon: Option<String>,
    pub cf_bbs_rewrite: i8,
    pub cf_delay_sec: i32,
    pub cf_extension: Option<String>,
    pub cf_captcha: Option<String>,
    pub cf_recaptcha_ver: Option<String>,
    pub cf_recaptcha_site_key: Option<String>,
    pub cf_recaptcha_secret_key: Option<String>,
    pub cf_mail_server: Option<String>,
    pub cf_mail_server_port: i32,
    pub cf_mail_server_id: Option<String>,
    pub cf_mail_server_pass: Option<String>,
    pub cf_mail_server_email: Option<String>,
    pub cf_use_member_photo: i8,
    pub cf_use_member_cert: i8,
    pub cf_use_email_cert: i8,
    pub cf_use_sns: i8,
    pub cf_use_kakao_cert: i8,
    pub cf_use_naver_cert: i8,
    pub cf_use_google_cert: i8,
    pub cf_use_facebook_cert: i8,
    pub cf_use_twitter_cert: i8,
    pub cf_use_payco_cert: i8,
    pub cf_use_member_login: i8,
    pub cf_social_login_use: i8,
    pub cf_social_naver_client_id: Option<String>,
    pub cf_social_naver_secret: Option<String>,
    pub cf_social_naver_redirect: Option<String>,
    pub cf_social_google_client_id: Option<String>,
    pub cf_social_google_secret: Option<String>,
    pub cf_social_google_redirect: Option<String>,
    pub cf_social_facebook_client_id: Option<String>,
    pub cf_social_facebook_secret: Option<String>,
    pub cf_social_facebook_redirect: Option<String>,
    pub cf_social_twitter_client_id: Option<String>,
    pub cf_social_twitter_secret: Option<String>,
    pub cf_social_twitter_redirect: Option<String>,
    pub cf_social_kakao_client_id: Option<String>,
    pub cf_social_kakao_secret: Option<String>,
    pub cf_social_kakao_redirect: Option<String>,
    pub cf_social_payco_client_id: Option<String>,
    pub cf_social_payco_secret: Option<String>,
    pub cf_social_payco_redirect: Option<String>,
    pub cf_payco_service: i8,
    pub cf_1: Option<String>,
    pub cf_2: Option<String>,
    pub cf_3: Option<String>,
    pub cf_4: Option<String>,
    pub cf_5: Option<String>,
    pub cf_6: Option<String>,
    pub cf_7: Option<String>,
    pub cf_8: Option<String>,
    pub cf_9: Option<String>,
    pub cf_10: Option<String>,
}

/// Point table (g5_point)
#[derive(Debug, FromRow, Serialize)]
pub struct Point {
    pub po_id: i32,
    pub mb_id: String,
    pub po_datetime: NaiveDateTime,
    pub po_subject: String,
    pub po_point: i32,
    pub po_use_point: i32,
    pub po_expire_point: i32,
    pub po_content: String,
    pub po_rel_table: Option<String>,
    pub po_rel_id: Option<String>,
    pub po_rel_action: Option<String>,
    pub mb_id_rel: Option<String>,
    pub po_1: Option<String>,
    pub po_2: Option<String>,
    pub po_3: Option<String>,
    pub po_4: Option<String>,
    pub po_5: Option<String>,
}

/// Menu table (g5_menu) - Gnuboard5 실제 구조
#[derive(Debug, FromRow, Serialize)]
pub struct Menu {
    pub me_id: i32,
    pub me_code: String,
    pub me_name: String,
    pub me_link: String,
    pub me_target: Option<String>,
    pub me_order: i32,
    pub me_use: i8,
    pub me_mobile_use: i8,
}

/// New board table (g5_board_new)
#[derive(Debug, FromRow, Serialize)]
pub struct BoardNew {
    pub bn_id: i32,
    pub mb_id: Option<String>,
    pub bo_table: String,
    pub wr_id: i32,
    pub wr_parent: i32,
    pub bn_datetime: NaiveDateTime,
    pub bn_read: i32,
    pub bn_ip: Option<String>,
}

/// Popular search table (g5_popular)
#[derive(Debug, FromRow, Serialize)]
pub struct Popular {
    pub pp_id: i32,
    pub pp_word: String,
    pub pp_date: String,
    pub pp_count: i32,
}

#[derive(Clone)]
pub struct AppState {
    pub pool: MySqlPool,
    pub config: Arc<AppConfig>,
}

#[derive(Debug, Deserialize)]
pub struct LoginRequest {
    pub mb_id: String,
    pub mb_password: String,
    pub device_id: Option<String>,
}
