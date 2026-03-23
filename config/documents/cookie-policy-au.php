<?php
/*
 * This document is intentionally not translatable, as it is intended to be for US citizens, and should therefore always be in English
 *
 * */
defined('ABSPATH') or die("you do not have access to this page!");

$this->pages['au']['cookie-statement']['document_elements'] = array(
    array(
        'content' => '<i>' . cmplz_sprintf("This page was last changed on %s, last checked on %s and applies to citizens of Australia. ", '[publish_date]', '[checked_date]') . '</i><br />',
    ),
    array(
        'title' => 'Introduction',
        'content' => cmplz_sprintf('Our website, %s (hereinafter: "the website") uses cookies and other related technologies (for convenience all technologies are referred to as "cookies"). Cookies are also placed by third parties we have engaged. In the document below we inform you about the use of cookies on our website.', '[domain]' ),
    ),

    array(
        'title' => 'What are cookies?',
        'content' => 'A cookie is a small simple file that is sent along with pages of this website and stored by your browser on the hard drive of your computer or another device. The information stored therein may be returned to our servers or to the servers of the relevant third parties during a subsequent visit.',
    ),
    array(
        'title' => 'What are scripts?',
        'content' => 'A script is a piece of program code that is used to make our website function properly and interactively. This code is executed on our server or on your device.',
    ),
    array(
        'title' => 'What is a web beacon?',
        'content' => 'A web beacon (or a pixel tag) is a small, invisible piece of text or image on a website that is used to monitor traffic on a website. In order to do this, various data about you is stored using web beacons.',
        'callback_condition' => 'NOT cmplz_uses_only_functional_cookies',
    ),
    array(
        'title' => 'Consent',
        'content' => 'When you visit our website for the first time, we will show you a pop-up with an explanation about cookies. You do have the right to opt-out and to object against the further use of non-functional cookies.',
    ),
    array(
	    'subtitle' => 'Manage your consent settings',
	    'p' => false,
	    'content' => '[cmplz-manage-consent]',
    ),
	array(
		'subtitle' => 'Vendors',
		'p' => false,
		'content' => '[cmplz-tcf-vendors]',
		'callback_condition' => array(
			'cmplz_tcf_active',
			'cmplz_site_shares_data',
		),
	),
    array(
        'content' =>'You can also disable the use of cookies via your browser, but please note that our website may no longer work properly.',
    ),

    array(
        'title' => 'Cookies',
    ),
    array(
        'subtitle' => 'Technical or functional cookies',
        'content' => 'Some cookies ensure that certain parts of the website work properly and that your user preferences remain known. By placing functional cookies, we make it easier for you to visit our website. This way, you do not need to repeatedly enter the same information when visiting our website and, for example, the items remain in your shopping cart until you have paid. We may place these cookies without your consent.',
    ),

    //analytical
    array(
        'subtitle' => 'Analytical cookies',
        'content' => 'We use analytical cookies to optimize the website experience for our users. With these analytical cookies we get insights in the usage of our website.',
        'callback_condition' => 'cmplz_uses_statistics',
        'condition' => array('compile_statistics' => 'NOT no'),
    ),

    //ads
    array(
        'subtitle' => 'Advertising cookies',
        'content' => cmplz_sprintf('On this website we use advertising cookies, enabling us to personalize the advertisements for you, and we (and third parties) gain insights into the campaign results. This happens based on a profile we create based on your click and surfing on and outside %s. With these cookies you, as website visitor are linked to a unique ID, so you do not see the same ad more than once for example.', '[domain]'),
        'condition' => array(
            'uses_ad_cookies' => 'yes',
            'uses_ad_cookies_personalized' => 'NOT no'
        ),
    ),

    array(
        'subtitle' => 'Advertising cookies',
        'content' => cmplz_sprintf('On this website we use advertising cookies, enabling us to gain insights into the campaign results. This happens based on a profile we create based on your behaviour on %s. With these cookies you, as website visitor, are linked to a unique ID but these cookies will not profile your behaviour and interests to serve personalised ads.', '[domain]'),
        'condition' => array(
            'uses_ad_cookies' => 'yes',
            'uses_ad_cookies_personalized' => 'no'
        ),
    ),

    array(
        'content' => 'You can object to the tracking by these cookies by clicking the "Manage Consent" button.',
        'condition' => array(
            'uses_ad_cookies' => 'yes',
        ),
    ),

	array(
		'subtitle' => 'Marketing/Tracking cookies', 'cookie policy',
		'content' => 'Marketing/Tracking cookies are cookies or any other form of local storage, used to create user profiles to display advertising or to track the user on this website or across several websites for similar marketing purposes.',
//		'condition' => array(
//			'uses_ad_cookies' => 'no',
//		),
		'callback_condition' => 'cmplz_uses_marketing_cookies',
	),

	array(
		'subtitle' => 'Social media', 'Legal document cookie policy:paragraph title',
		'content' => 'On our website, we have included content to promote web pages (e.g. “like”, “pin”) or share (e.g. “tweet”) on social networks. This content is embedded with code derived from third parties and places cookies. This content might store and process certain information for personalized advertising.',
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'EMPTY',
		),
	),
	array(
		'subtitle' => 'Social media', 'Legal document cookie policy:paragraph title',
		'content' => cmplz_sprintf('On our website, we have included content from %s to promote web pages (e.g. “like”, “pin”) or share (e.g. “tweet”) on social networks like %s. This content is embedded with code derived from %s and places cookies. This content might store and process certain information for personalized advertising.', '[comma_socialmedia_on_site]', '[comma_socialmedia_on_site]', '[comma_socialmedia_on_site]'),
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'NOT EMPTY',
		),
	),

	array(
		'content' => 'Please read the privacy statement of these social networks (which can change regularly) to read what they do with your (personal) data which they process using these cookies. The data that is retrieved is anonymized as much as possible.'.' '.
		             cmplz_sprintf( _n( '%s is located in the United States.', '%s are located in the United States.',  cmplz_count_socialmedia(), 'complianz-gdpr'  ) ,'[comma_socialmedia_on_site]' ),
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'NOT EMPTY',
		),
	),

	array(
		'content' => 'Please read the privacy statement of these social networks (which can change regularly) to read what they do with your (personal) data which they process using these cookies. The data that is retrieved is anonymized as much as possible.',
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'EMPTY',
		),
	),

	'cookie_names' => array(
		'title' => 'Placed cookies',
		'callback' => 'cmplz_used_cookies',
	),

	array(
		'title' => 'Enabling/disabling and deleting cookies',
		'content' => 'You can use your internet browser to automatically or manually delete cookies. You can also specify that certain cookies may not be placed. Another option is to change the settings of your internet browser so that you receive a message each time a cookie is placed. For more information about these options, please refer to the instructions in the Help section of your browser.',
	),

	'enable-disable-removal-cookies-2' => array(
		'content' => 'Please note that our website may not work properly if all cookies are disabled. If you do delete the cookies in your browser, they will be placed again after your consent when you visit our website again.',
	),

    array(
        'title' => 'Your rights with respect to personal data',
        'p'=> false,
        'content' =>
            '<p>You have the following rights with respect to your personal data:</p>' .
            '<ul>
                <li>you may submit a request for access to the data we process about you;</li>
                <li>you may request an overview, in a commonly used format, of the data we process about you;</li>
                <li>you may request correction or deletion of the data if it is incorrect or not or no longer relevant for any purpose under the Privacy Act.</li>
            </ul>' .
            '<p>To exercise these rights, please contact us. Please refer to the contact details at the bottom of this cookie statement. If you have a complaint about how we handle your data, we would like to hear from you.</p>',
    ),



	array(
        'title' => 'Contact details', 'Legal document cookie policy:',
        'content' => 'For questions and/or comments about our Cookie Policy and this statement, please contact us by using the following contact details:',
    ),

    array(
        'content' => '<span class="cmplz-contact-organisation">[organisation_name]</span><br />
                    <span class="cmplz-contact-address">[address_company]</span><br />
                  <span class="cmplz-contact-country">[country_company]</span><br />
                    <span class="cmplz-contact-domain">' . _x('Website:', 'Legal document cookie policy', 'complianz-gdpr') . ' [domain]</span><br>
                    <span class="cmplz-contact-email">' . _x('Email:', 'Legal document cookie policy', 'complianz-gdpr') . ' [email_company]</span><br>
                    <span class="cmplz-contact-telephone">[telephone_company]</span>',
    ),

    array(
        'content' => cmplz_sprintf('This Cookie Policy was synchronised with %scookiedatabase.org%s on %s.','<a href="https://cookiedatabase.org/" target="_blank">', '</a>', '[sync_date]'),
        'callback_condition' => array(
	        'cmplz_cdb_reference_in_policy',
        )
    ),

);
