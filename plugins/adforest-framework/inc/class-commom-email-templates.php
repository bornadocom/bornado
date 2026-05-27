<?php
class CommomEmailTemplates {

    function __construct() {
        add_action('adforest_commom_email_templates', array($this, 'adforest_commom_email_templates_callback'), 0, 1);
    }

    public function adforest_commom_email_templates_callback($opt_name = '') {

        Redux::setSection($opt_name, array(
            'title' => __('Package Expiry Notification', "redux-framework"),
            'id' => 'sb_email_templates15',
            'desc' => '',
            'subsection' => true,
            'fields' => array(
                array(
                    'id' => 'sb_package_expiray_subject',
                    'type' => 'text',
                    'title' => __('Package Expiry SUBJECT', "redux-framework"),
                    'default' => 'Get message from Adforest profile.',
                ),
                array(
                    'id' => 'sb_package_expiry_from',
                    'type' => 'text',
                    'title' => __('Package Expiry FROM', "redux-framework"),
                    'desc' => __('NAME valid@email.com is compulsory as we gave in default.', "redux-framework"),
                    'default' => get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                ),
                array(
                    'id' => 'sb_package_expiry_msg',
                    'type' => 'editor',
                    'title' => __('Package Expiry MESSAGE', "redux-framework"),
                    'args' => array(
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'wpautop' => false,
                    ),
                    'desc' => __('%site_name% , %package_subcriber% , %package_name% , %no_of_days% will be translated accordingly.', "redux-framework"),
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
                <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
                <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
                <tbody>
                <tr>
                <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                <td class="alert" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff">

                A Designing and development company</td>
                </tr>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello %package_subcriber%</span><span style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;"><b>,</b></span></p>
                <br />
                Your Ads Package %package_name% will be expire after %no_of_days% Days. Please renew your package. 
                <br />

                &nbsp;
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
                </td>
                </tr>
                </tbody>
                </table>
                </td>
                </tr>
                </tbody>
                </table>
                <div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
                <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                <td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
                </tr>
                </tbody>
                </table>
                </div>
                &nbsp;

                </div></td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
                </tr>
                </tbody>
                </table>
                &nbsp;',
                ),
            )
        ));
        

                Redux::setSection($opt_name, array(
            'title' => __('Ad Expiry Notification', "redux-framework"),
            'id' => 'sb_email_templates_ad_expiry',
            'desc' => '',
            'subsection' => true,
            'fields' => array(


         array(
            'id' => 'ad_expiry_notification',
            'type' => 'switch',
            'title' => __('Ad Expiry Notification', 'adforest'),
            'desc' => __('<b class="sb-admin-note"> Note : </b> This functionality works hiddenly notify the users before and after ad expiry .This option takes a lot of load so any one who wishes to choose this option must have a good server that can support heavy load.', 'adforest'),
            'default' => false,
        ),
        array(
            'id' => 'ad_expire_notify_before',
            'type' => 'text',
            'title' => __('Ad Expiry Notification before', 'adforest'),
            'subtitle' => __('add the number of days before package expiry notification', 'adforest'),
            'default' => 3,
            'desc' => __('should be integer value. <b>( Days )</b>', 'adforest'),
            'required' => array('ad_expiry_notification', '=', array(true)),
        ), 

                array(
                    'id' => 'sb_ad_expiray_subject',
                    'type' => 'text',
                    'title' => __('Ads Expiry subject', "redux-framework"),
                    'default' => 'Get message from Adforest profile.',
                    'required' => array('ad_expiry_notification', '=', array(true)),
                ),
                array(
                    'id' => 'sb_ad_expiry_from',
                    'type' => 'text',
                    'title' => __('Ad Expiry From', "redux-framework"),
                    'desc' => __('NAME valid@email.com is compulsory as we gave in default.', "redux-framework"),
                    'default' => get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                    'required' => array('ad_expiry_notification', '=', array(true)),
                ),
                array(
                    'id' => 'sb_ad_expiry_msg',
                    'type' => 'editor',
                    'title' => __('Ad Expiry Message', "redux-framework"),
                    'args' => array(
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'wpautop' => false,
                    ),
                    'required' => array('ad_expiry_notification', '=', array(true)),
                    'desc' => __('%site_name% , %ad_author% , %ad_title% , %no_of_days% will be translated accordingly.', "redux-framework"),
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
                <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
                <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
                <tbody>
                <tr>
                <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                <td class="alert" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff">

                A Designing and development company</td>
                </tr>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello %ad_author%</span><span style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;"><b>,</b></span></p>
                <br />
                Your Ads  %ad_title% will be expire after %no_of_days% Days.  
                <br />

                &nbsp;
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
                </td>
                </tr>
                </tbody>
                </table>
                </td>
                </tr>
                </tbody>
                </table>
                <div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
                <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                <td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
                </tr>
                </tbody>
                </table>
                </div>
                &nbsp;

                </div></td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
                </tr>
                </tbody>
                </table>
                &nbsp;',
                ),



array(
                    'id' => 'sb_ad_after_expiry_msg',
                    'type' => 'editor',
                    'title' => __('After Ad Expiry Message', "redux-framework"),
                    'args' => array(
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'wpautop' => false,
                    ),
                    'required' => array('ad_expiry_notification', '=', array(true)),
                    'desc' => __('%site_name% , %ad_author% , %ad_title% , %no_of_days% will be translated accordingly.', "redux-framework"),
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
                <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
                <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
                <tbody>
                <tr>
                <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                <td class="alert" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff">

                A Designing and development company</td>
                </tr>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello %ad_author%</span><span style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;"><b>,</b></span></p>
                <br />
                Your Ads %ad_title% has been expired.  
                <br />

                &nbsp;
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
                </td>
                </tr>
                </tbody>
                </table>
                </td>
                </tr>
                </tbody>
                </table>
                <div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
                <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                <td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
                </tr>
                </tbody>
                </table>
                </div>
                &nbsp;

                </div></td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
                </tr>
                </tbody>
                </table>
                &nbsp;',
                ),
            )
        ));

        Redux::setSection($opt_name, array(
            'title' => __('Bid Winner Email', "redux-framework"),
            'id' => 'sb_email_templates14',
            'desc' => '',
            'subsection' => true,
            'fields' => array(
                array(
                    'id' => 'sb_new_bid_winner_subject',
                    'type' => 'text',
                    'title' => __('Bid email subject', "redux-framework"),
                    'desc' => __('%site_name% will be translated accordingly.', "redux-framework"),
                    'default' => 'New Bid - Adforest',
                ),
                array(
                    'id' => 'sb_new_bid_winner_from',
                    'type' => 'text',
                    'title' => __('Bid email FROM', "redux-framework"),
                    'args' => array(
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'wpautop' => false,
                    ),
                    'desc' => __('FROM: NAME valid@email.com is compulsory as we gave in default.', "redux-framework"),
                    'default' => 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                ),
                array(
                    'id' => 'sb_new_bid_winner_message',
                    'type' => 'editor',
                    'title' => __('Bid email template', "redux-framework"),
                    'desc' => __('%site_name% , %bid_winner_name% , %bid_link%  will be translated accordingly.', "redux-framework"),
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
                <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
                <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
                <tbody>
                <tr>
                <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                <td class="alert" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff"><img class="alignnone size-full wp-image-1437" src="http://adforest.scriptsbundle.com/wp-content/uploads/2017/03/SB-logo.png" width="80" height="80"  alt="image" />

                A Designing and development company</td>
                </tr>
                <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello %bid_winner_name%</span><span style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;"><b>,</b></span></p>
               
                
                Congratulation ! you are the winner of Bid.
                <br />
                Bid Link: %bid_link%

                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p><p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p></td></tr></tbody></table></td></tr></tbody></table><div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;"><table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0"><tbody><tr><td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td></tr></tbody></table></div>&nbsp;</div></td><td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td></tr></tbody></table>&nbsp;',
                ),
            )
        ));
    }

}

new CommomEmailTemplates();