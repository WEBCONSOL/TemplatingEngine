<?php

define('GX2CMS_PLATFORM_TAG', 'gx2cms');
define('GX2CMS_TECHNOLOGY_SLY_TAG', 'sly');
define('INJECT_CSS_SFX', '-inject-stylesheet');
define('INJECT_JS_SFX', '-inject-javascript');
define('GX2CMS_INJECT_CSS', GX2CMS_PLATFORM_TAG.INJECT_CSS_SFX);
define('GX2CMS_INJECT_JS', GX2CMS_PLATFORM_TAG.INJECT_JS_SFX);
define('GX2CMS_STYLESHEET_PLACEHOLDER', GX2CMS_PLATFORM_TAG.'-stylesheet-placeholder');
define('GX2CMS_JAVASCRIPT_PLACEHOLDER', GX2CMS_PLATFORM_TAG.'-javascript-placeholder');
define('TMPL_CLIENTLIB_ROOT', 'template-clientlib-root');
define('COMP_CLIENTLIB_ROOT', 'component-clientlib-root');
define('GX2CMS_NEGATE_SIGN', 'GX2CMS_NEGATE_SIGN');
define('GX2CMS_EQ_SIGN', 'GX2CMS_EQ_SIGN');
define('GX2CMS_AND_SIGN', 'GX2CMS_AND_SIGN');
define('GX2CMS_OR_SIGN', 'GX2CMS_OR_SIGN');
define('GX2CMS_GT_SIGN', 'GX2CMS_GT_SIGN');
define('GX2CMS_LT_SIGN', 'GX2CMS_LT_SIGN');
define('GX2CMS_SINGLE_QUOTE', 'GX2CMS_SINGLE_QUOTE');
define('GX2CMS_DOUBLE_QUOTE', 'GX2CMS_DOUBLE_QUOTE');
define('GX2CMS_BRACKET_OPEN', 'GX2CMS_BRACKET_OPEN');
define('GX2CMS_BRACKET_CLOSE', 'GX2CMS_BRACKET_CLOSE');
define('GX2CMS_COMMENT_START', '${!--//');
define('GX2CMS_COMMENT_END', '//--}');

if (!defined('GX2CMS_CONTAINER_PARAGRAPH_SYSTEM')) {define('GX2CMS_CONTAINER_PARAGRAPH_SYSTEM', '/extensions/components/wcm/container');}
if (!defined('KEY_HTTP_REQUEST')) {define('KEY_HTTP_REQUEST', 'httpRequest');}
if (!defined('KEY_CURRENT_PAGE')) {define('KEY_CURRENT_PAGE', 'currentPage');}
if (!defined("WC_SESSION_LOGIN_KEY")) {define("WC_SESSION_LOGIN_KEY", "wc_login_uid");}
if (!defined("WC_SESSION_DATA_KEY")) {define("WC_SESSION_DATA_KEY", "session_user_data");}
if (!defined("DS")) {define("DS", DIRECTORY_SEPARATOR);}